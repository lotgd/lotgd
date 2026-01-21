<?php
declare(strict_types=1);

namespace LotGD2\Game\Scene\SceneTemplate;

use LotGD2\Entity\Action;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Entity\Mapped\Scene;
use LotGD2\Entity\Mapped\Stage;
use LotGD2\Game\Character\Gold;
use LotGD2\Game\Scene\SceneAttachment\SimpleFormAttachment;
use LotGD2\Game\Stage\ActionService;
use LotGD2\Repository\AttachmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type BankTemplateConfiguration array{
 *     tellerName: string,
 *     accountName: string,
 *     text: array{
 *         deposit: string,
 *         withdraw: string,
 *     }
 * }
 * @implements SceneTemplateInterface<BankTemplateConfiguration>
 */
#[Autoconfigure(public: true)]
readonly class BankTemplate implements SceneTemplateInterface
{
    use DefaultSceneTemplate;

    public function __construct(
        private LoggerInterface $logger,
        private Gold $gold,
        private AttachmentRepository $attachmentRepository,
        private ActionService $actionService,
    ) {
    }

    public static function validateConfiguration(array $config): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->define("tellerName")
            ->required()
            ->allowedTypes("string")
            ->default("Elessa");

        $resolver
            ->define("accountName")
            ->required()
            ->allowedTypes("string")
            ->default("default");

        $resolver
            ->define("text")
            ->default(function (OptionsResolver $resolver) {
                $resolver
                    ->define("deposit")
                    ->required()
                    ->allowedTypes('string');

                $resolver
                    ->define("withdraw")
                    ->required()
                    ->allowedTypes('string');
            });

        return $resolver->resolve($config);
    }

    public function onSceneChange(Stage $stage, Action $action, Scene $scene): void
    {
        $op = $action->getParameters()["op"] ?? "";
        $this->logger->debug("Called BankTemplate::onSceneChange, op={$op}");

        $stage->setContext([
            "tellerName" => $scene->getTemplateConfig()["tellerName"],
        ]);

        match ($op) {
            "depositOrWithdraw" => $this->depositOrWithdrawAction($stage, $action, $scene),
            default => $this->defaultAction($stage, $action, $scene),
        };
    }

    public function depositOrWithdrawAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called BankTemplate::depositOrWithdrawAction");

        $character = $stage->getOwner();
        $actionData = $action->getParameters()[SimpleFormAttachment::ActionParameterName];
        $amount = (int)abs($actionData["amount"] ?? 0);

        if (isset($actionData["withdraw"]) and $actionData["withdraw"] === true) {
            if ($amount === 0) {
                // If amount is 0, we withdraw everything
                $amount = $this->getGoldInBank($character, $scene->getTemplateConfig());
            }

            $amount = min($amount, $this->getGoldInBank($character, $scene->getTemplateConfig()));

            $this->addGoldInBank($character, $scene->getTemplateConfig(), -$amount);
            $this->gold->addGold($amount);

            $this->logger->debug("Withdrew {$amount} gold from the bank (bank account name: {$scene->getTemplateConfig()['accountName']})");

            $stage->setDescription($scene->getTemplateConfig()["text"]["withdraw"]);
        } else {
            if ($amount === 0) {
                // If amount is 0, we deposit everything
                $amount = $this->gold->getGold();
            }

            $amount = min($amount, $this->gold->getGold());

            $this->addGoldInBank($character, $scene->getTemplateConfig(), $amount);
            $this->gold->addGold(-$amount);

            $this->logger->debug("Deposited {$amount} gold to the bank (bank account name: {$scene->getTemplateConfig()['accountName']})");

            $stage->setDescription($scene->getTemplateConfig()["text"]["deposit"]);
        }

        $stage
            ->addContext("amount", $amount)
            ->addContext("goldInBank", $this->getGoldInBank($character, $scene->getTemplateConfig()))
            ->addContext("goldInHand", $this->gold->getGold())
        ;
    }

    public function defaultAction(Stage $stage, Action $action, Scene $scene): void
    {
        $this->logger->debug("Called BankTemplate::defaultAction");

        $attachment = $this->attachmentRepository->findOneByAttachmentClass(SimpleFormAttachment::class);

        $stage
            ->addContext("goldInBank", $this->getGoldInBank($stage->getOwner(), $scene->getTemplateConfig()));

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

            $this->logger->debug("Add SimpleFormAttachment (id={$attachment->getId()})");
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
}