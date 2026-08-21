<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Game\Character\CharacterService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class GenderHandler
{
    const string GenderProperty = "lotgd2_gender";
    const string PronounsGenderProperty = "pronouns";
    const string PartnerGenderProperty = "partner";

    const array PronounValues = ["male", "female", "other"];

    public function getPreferredPronouns(Character $character): string
    {
        $pronouns = $character->getProperty(self::GenderProperty) ?? [];
        if (isset($pronouns[self::PronounsGenderProperty])) {
            $value = $pronouns[self::PronounsGenderProperty];
            if (in_array($value, self::PronounValues)) {
                return $value;
            }
        }

        return "other";
    }

    #[AsEventListener(event: CharacterService::CharacterFormExtensionEventName)]
    public function onCharacterEdit(FormExtensionEvent $event): void
    {
        $builder = $event->builder;

        $builder->add(
            $builder->create(GenderHandler::GenderProperty, FormType::class, [
                'property_path' => 'properties['. GenderHandler::GenderProperty .']',
                "by_reference" => false,
                "label" => "Gender settings",
                "help" => <<<TXT
                            These settings influence primarily texts that refer to the character, or events that happen
                            to your character.
                            TXT,
            ])
            ->add(GenderHandler::PronounsGenderProperty, ChoiceType::class, [
                "choices" => [
                    "Male (he/him)" => "male",
                    "Female (she/her)" => "female",
                    "Other (they/them)" => "other",
                ],
                "help" => "This setting is used to refer to your character in third person. Can be changed afterwards.",
            ])
            ->add(GenderHandler::PartnerGenderProperty, ChoiceType::class, [
                "choices" => [
                    "Male" => "male",
                    "Female" => "female",
                ],
                "help" => <<<TXT
                        This setting is used in texts to determine the preferred partner. In the classic inn,
                        this is the decision between Seth or Violet; in specials, it could be the difference between
                        a prince or a princess.
                        TXT,
            ])
        );
    }
}
