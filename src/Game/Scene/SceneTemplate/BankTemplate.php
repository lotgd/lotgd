<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Entity\Paragraph;
use LotGD2\Event\StageChangeEvent;
use LotGD2\Form\Scene\SceneTemplate\BankTemplateType;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Character\Health;
use LotGD2\Game\GameTime\NewDay;
use LotGD2\Game\Random\DiceBagInterface;
use LotGD2\Game\Scene\SceneAttachment\SimpleFormAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use LotGD2\Repository\SceneRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @phpstan-type BankTemplateConfiguration array{
 *     tellerName: string,
 *     accountName: string,
 *     minInterest: int,
 *     maxInterest: int,
 *     maxGoldInBank: int,
 *     turnsLeftBeforeInterest: int,
 *     text: array{
 *         deposit: string,
 *         withdraw: string,
 *     }
 * }
 * @implements SceneTemplateInterface<BankTemplateConfiguration>
 */
#[Autoconfigure(public: true)]
#[TemplateType(BankTemplateType::class)]
class BankTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    public function __construct(
        private LoggerInterface $logger,
        private Stopwatch $stopwatch,
        private AttachmentRepository $attachmentRepository,
        private SceneRepository $sceneRepository,
        private DiceBagInterface $diceBag,
        private ActionService $actionService,
        private Gold $gold,
    ) {
    }

    public function onSceneChange(): void
    {
        $op = $this->action->getParameter("op", "");
        $this->logger->debug("Called BankTemplate::onSceneChange, op={$op}");

        $sceneText = $this->stage->paragraphs[Stage::SceneText] ?? null;
        $sceneText?->addContext("tellerName", $this->scene->templateConfig["tellerName"]);
        $sceneText?->addContext("goldInBank", $this->getGoldInBank($this->stage->owner, $this->scene->templateConfig));

        match ($op) {
            "depositOrWithdraw" => $this->depositOrWithdrawAction(),
            default => $this->defaultAction(),
        };
    }

    public function depositOrWithdrawAction(): void
    {
        $this->logger->debug("Called BankTemplate::depositOrWithdrawAction");

        $character = $this->stage->owner;
        $actionData = $this->action->getParameters()[SimpleFormAttachment::ActionParameterName] ?? [];
        $amount = (int)abs($actionData["amount"] ?? 0);

        if (isset($actionData["withdraw"]) and $actionData["withdraw"] === true) {
            if ($amount === 0) {
                // If amount is 0, we withdraw everything
                $amount = $this->getGoldInBank($character, $this->scene->templateConfig);
            }

            $amount = min($amount, $this->getGoldInBank($character, $this->scene->templateConfig));

            $this->addGoldInBank($character, $this->scene->templateConfig, -$amount);
            $this->gold->addGold($amount);

            $accountName = $this->scene->templateConfig['accountName'] ?? 'defaults';
            $this->logger->debug("Withdrew {$amount} gold from the bank (bank account name: {$accountName})");

            $paragraph = new Paragraph(
                id: "lotgd2.paragraph.bankTemplate.withdraw",
                text: $this->scene->templateConfig["text"]["withdraw"],
            );
        } else {
            if ($amount === 0) {
                // If amount is 0, we deposit everything
                $amount = $this->gold->getGold();
            }

            $amount = min($amount, $this->gold->getGold());

            $this->addGoldInBank($character, $this->scene->templateConfig, $amount);
            $this->gold->addGold(-$amount);

            $accountName = $this->scene->templateConfig['accountName'] ?? 'defaults';
            $this->logger->debug("Deposited {$amount} gold to the bank (bank account name: {$accountName})");

            $paragraph = new Paragraph(
                id: "lotgd2.paragraph.bankTemplate.deposit",
                text: $this->scene->templateConfig["text"]["deposit"],
            );
        }

        $paragraph->context = [
            "amount" => $amount,
            "goldInBank" => $this->getGoldInBank($character, $this->scene->templateConfig),
            "goldInHand" => $this->gold->getGold(),
        ];

        $this->stage->paragraphs = [$paragraph];
    }

    public function defaultAction(): void
    {
        $this->logger->debug("Called BankTemplate::defaultAction");

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(SimpleFormAttachment::class);

        if ($attachment) {
            $formAction = new Action($this->scene, parameters: ["op" => "depositOrWithdraw"]);
            $this->actionService->addHiddenAction($this->stage, $formAction);

            $this->stage->addAttachment($attachment, [
                "actionId" => $formAction->id,
                "form" => [
                    ["amount", IntegerType::class, ["label" => "Amount", "required" => false]],
                    ["withdraw", CheckboxType::class, ["label" => "Withdraw", "required" => false]],
                ],
            ]);

            $this->logger->debug("Add SimpleFormAttachment (id={$attachment->id})");
        } else {
            $this->logger->critical("Cannot attach attachment " . SimpleFormAttachment::class . ": Not installed.");
        }
    }

    /**
     * @param Character $character
     * @param BankTemplateConfiguration $templateConfig
     * @return int
     */
    private function getGoldInBank(Character $character, array $templateConfig): int
    {
        $bankProperties = $character->getProperty("bank", []);
        $accountName = $templateConfig["accountName"] ?? 'defaults';
        if (empty($bankProperties)) {
            return 0;
        } elseif (isset($bankProperties[$accountName])) {
            return $bankProperties[$accountName];
        } else {
            return 0;
        }
    }

    /**
     * @param Character $character
     * @param BankTemplateConfiguration $templateConfig
     * @param int $amount
     * @return void
     */
    private function setGoldInBank(Character $character, array $templateConfig, int $amount): void
    {
        $bankProperties = $character->getProperty("bank", []);
        $bankProperties[$templateConfig["accountName"] ?? "defaults"] = $amount;
        $character->setProperty("bank", $bankProperties);
    }

    /**
     * @param Character $character
     * @param BankTemplateConfiguration $templateConfig
     * @param int $amount
     * @return void
     */
    private function addGoldInBank(Character $character, array $templateConfig, int $amount): void
    {
        $this->setGoldInBank($character, $templateConfig, $this->getGoldInBank($character, $templateConfig) + $amount);
    }

    #[AsEventListener(event: NewDay::OnNewDayAfter)]
    public function onNewDayEvent(StageChangeEvent $event): void
    {
        $this->stopwatch->start("lotgd2.BankTemplate.onNewDayEvent");
        $defaultConfig = new BankTemplateType()->getDefaultData();

        $oldHealth = new Health(null, $event->characterBefore);

        $scenes = $this->sceneRepository->findBy(["templateClass" => self::class], ["id" => "ASC"]);
        $bankAccounts = [];
        foreach ($scenes as $scene) {
            $config = $scene->templateConfig;

            $bankAccount = $config["accountName"] ?? "default";
            if (isset($bankAccounts[$bankAccount])) {
                continue;
            }

            $bankAccounts[$bankAccount] = true;

            $i = $scene->id;

            $minInterest = $config["minInterest"] ?? $defaultConfig["minInterest"] ?? 0;
            $maxInterest = $config["maxInterest"] ?? $defaultConfig["maxInterest"] ?? 0;
            $maxGoldInBank = $config["maxGoldInBank"] ?? 10_000;
            $turnsLeftBeforeInterest = $config["turnsLeftBeforeInterest"] ?? 4;

            $interestRate = $this->diceBag->pseudoBell((int)round($minInterest), (int)round($maxInterest)) / 100;
            $goldInBank = $this->getGoldInBank($event->character, $config);
            $interest = (int)round($interestRate * $goldInBank);

            $context = [
                "bankName" => $scene->title,
                "bankInterestRate" => $interestRate*100,
                "bankInterest" => $interest,
            ];

            $text = null;

            if ($goldInBank < 0) {
                // Character has dept
                if ($goldInBank < -$maxGoldInBank) {
                    // Character owes a dept larger than the limit; let's not bankrupt him further.
                    $interest = 0;
                    $text = "The bank <.{{ bankName }}.> forgoes their right to collect interest on your dept. You owe enough money already.";
                } else {
                    $text = "The bank <.{{ bankName }}.>'s interest rate today is {{ bankInterestRate }}%. Your dept increases by an additional {{ bankInterest|abs }} gold.";
                }
            } elseif ($goldInBank > 0) {
                // Character has money
                if ($goldInBank > $maxGoldInBank) {
                    // Character has more gold than the maximum; the bank does not pay out interest.
                    $interest = 0;
                    $text = "The bank <.{{ bankName }}.> does not pay out your interest to retain solvency. You have already enough savings.";
                } elseif ($turnsLeftBeforeInterest >= 0 && $turnsLeftBeforeInterest <= $oldHealth->getTurns()) {
                    $text = "The bank <.{{ bankName }}.>'s interest rate today is {{ bankInterestRate }}%, but you will not earn interest. This bank only gives interest to those who work.";
                } else {
                    $text = "The bank <.{{ bankName }}.>'s interest rate today is {{ bankInterestRate }}%. You earned {{ bankInterest|abs }} gold interest.";
                }
            }

            if ($text) {
                $event->stage->addParagraph(new Paragraph(
                    id: "lotgd2.paragraph.BankTemplate.onNewDay",
                    text: $text,
                    context: $context,
                ));
            }

            $this->addGoldInBank($event->character, $config, $interest);
        }

        $this->stopwatch->stop("lotgd2.BankTemplate.onNewDayEvent");
    }
}