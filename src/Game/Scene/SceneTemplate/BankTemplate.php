<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Attribute\TemplateType;
use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
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
readonly class BankTemplate implements SceneTemplateInterface
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

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameters()["op"] ?? "";
        $this->logger->debug("Called BankTemplate::onSceneChange, op={$op}");

        $stage->context = [
            "tellerName" => $scene->templateConfig["tellerName"],
        ];

        match ($op) {
            "depositOrWithdraw" => $this->depositOrWithdrawAction($stage, $action, $scene),
            default => $this->defaultAction($stage, $action, $scene),
        };
    }

    public function depositOrWithdrawAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called BankTemplate::depositOrWithdrawAction");

        $character = $stage->owner;
        $actionData = $action->getParameters()[SimpleFormAttachment::ActionParameterName];
        $amount = (int)abs($actionData["amount"] ?? 0);

        if (isset($actionData["withdraw"]) and $actionData["withdraw"] === true) {
            if ($amount === 0) {
                // If amount is 0, we withdraw everything
                $amount = $this->getGoldInBank($character, $scene->templateConfig);
            }

            $amount = min($amount, $this->getGoldInBank($character, $scene->templateConfig));

            $this->addGoldInBank($character, $scene->templateConfig, -$amount);
            $this->gold->addGold($amount);

            $this->logger->debug("Withdrew {$amount} gold from the bank (bank account name: {$scene->templateConfig['accountName']})");

            $stage->description = $scene->templateConfig["text"]["withdraw"];
        } else {
            if ($amount === 0) {
                // If amount is 0, we deposit everything
                $amount = $this->gold->getGold();
            }

            $amount = min($amount, $this->gold->getGold());

            $this->addGoldInBank($character, $scene->templateConfig, $amount);
            $this->gold->addGold(-$amount);

            $this->logger->debug("Deposited {$amount} gold to the bank (bank account name: {$scene->templateConfig['accountName']})");

            $stage->description = $scene->templateConfig["text"]["deposit"];
        }

        $stage
            ->addContext("amount", $amount)
            ->addContext("goldInBank", $this->getGoldInBank($character, $scene->templateConfig))
            ->addContext("goldInHand", $this->gold->getGold())
        ;
    }

    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called BankTemplate::defaultAction");

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(SimpleFormAttachment::class);

        $stage
            ->addContext("goldInBank", $this->getGoldInBank($stage->owner, $scene->templateConfig));

        if ($attachment) {
            $formAction = new Action($scene, parameters: ["op" => "depositOrWithdraw"]);
            $this->actionService->addHiddenAction($stage, $formAction);

            $stage->addAttachment($attachment, [
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
        if (empty($bankProperties)) {
            return 0;
        } elseif (isset($bankProperties[$templateConfig["accountName"]])) {
            return $bankProperties[$templateConfig["accountName"]];
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
        $bankProperties[$templateConfig["accountName"]] = $amount;
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

    #[AsEventListener(event: NewDay::PostNewDay)]
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

            $event->stage->addContext("bankName{$i}", $scene->title);
            $event->stage->addContext("bankInterestRate{$i}", $interestRate*100);
            $event->stage->addContext("bankInterest{$i}", $interest);

            if ($goldInBank < 0) {
                // Character has dept
                if ($goldInBank < -$maxGoldInBank) {
                    // Character owes a dept larger than the limit; let's not bankrupt him further.
                    $interest = 0;
                    $event->stage->addDescription("The bank <.{{ bankName{$i} }}.> forgoes their right to collect interest on your dept. You owe enough money already.");
                } else {
                    $event->stage->addDescription("The bank <.{{ bankName{$i} }}.>'s interest rate today is {{ bankInterestRate{$i} }}%. Your dept increases by an additional {{ bankInterest{$i}|abs }} gold.");
                }
            } elseif ($goldInBank > 0) {
                // Character has money
                if ($goldInBank > $maxGoldInBank) {
                    // Character has more gold than the maximum; the bank does not pay out interest.
                    $interest = 0;
                    $event->stage->addDescription("The bank <.{{ bankName{$i} }}.> does not pay our your interest to retain solvency. You have already enough savings.");
                } elseif ($turnsLeftBeforeInterest >= 0 && $turnsLeftBeforeInterest <= $oldHealth->getTurns()) {
                    $event->stage->addDescription("The bank <.{{ bankName{$i} }}.>'s interest rate today is {{ bankInterestRate{$i} }}%, but you will not earn interest. This bank only gives interest to those who work.");
                } else {
                    $event->stage->addDescription("The bank <.{{ bankName{$i} }}.>'s interest rate today is {{ bankInterestRate{$i} }}%. You earned {{ bankInterest{$i}|abs }} gold interest.");
                }
            }

            $this->addGoldInBank($event->character, $config, $interest);
        }

        $this->stopwatch->stop("lotgd2.BankTemplate.onNewDayEvent");
    }
}