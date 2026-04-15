<?php
declare(strict_types=1);

namespace LotGD2\Game\Battle\BattleEvent;

use LotGD2\Entity\Battle\BattleMessage;
use LotGD2\Entity\Battle\FighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegenerationBuffEvent extends AbstractBattleEvent
{
    /**
     * @param FighterInterface $attacker
     * @param FighterInterface $defender
     * @param array{
     *     healAmount: int,
     *     effectSucceeds?: ?string,
     *     effectFails?: ?string,
     *     target: "attacker"|"defender",
     * } $context
     */
    public function __construct(
        private FighterInterface $attacker,
        private FighterInterface $defender,
        array $context
    ) {
        $resolver = new OptionsResolver();
        $resolver->define("healAmount")->allowedTypes("int")->required();
        $resolver->define("effectSucceeds")->allowedTypes("string", "null")->default(null);
        $resolver->define("effectFails")->allowedTypes("string", "null")->default(null);
        $resolver->define("target")->allowedTypes("string")->allowedValues("attacker", "defender");
        $this->context = $resolver->resolve($context);
    }

    public function apply(): void
    {
        parent::apply();

        if ($this->context["target"] === "attacker") {
            $this->attacker->damage(-$this->context["healAmount"]);
        } else {
            $this->defender->damage(-$this->context["healAmount"]);
        }
    }

    public function decorate(): ?BattleMessage
    {
        parent::decorate();

        if ($this->context["healAmount"] != 0) {
            $message = $this->context["effectSucceeds"];
        } else {
            $message = $this->context["effectFails"];
        }

        if (!$message) {
            return null;
        }

        return new BattleMessage($message, [
                "target" => $this->context["target"] === "attacker" ? $this->attacker : $this->defender,
                "defender" => $this->defender,
                "attacker" => $this->attacker,
                "amount" => $this->context["healAmount"],
            ]
        );
    }
}