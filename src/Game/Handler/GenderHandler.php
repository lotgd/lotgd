<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Mapped\Character;
use LotGD2\Event\FormExtensionEvent;
use LotGD2\Game\Character\CharacterService;
use LotGD2\Twig\Component\Admin\GameSettings;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class GenderHandler
{
    const string GenderProperty = "gender";
    const string PronounsGenderProperty = "pronouns";
    const string PartnerGenderProperty = "partner";

    const array PronounValues = ["male", "female", "other"];
    const array PartnerValues = ["male", "female"];

    public function getPreferredPronouns(Character $character): string
    {
        $gender = $character->getProperty(self::GenderProperty) ?? [];
        if (isset($gender[self::PronounsGenderProperty])) {
            $value = $gender[self::PronounsGenderProperty];
            if (in_array($value, self::PronounValues)) {
                return $value;
            }
        }

        return "other";
    }

    public function getPreferredPartner(Character $character): string
    {
        $gender = $character->getProperty(self::GenderProperty) ?? [];
        if (isset($gender[self::PartnerGenderProperty])) {
            $value = $gender[self::PartnerGenderProperty];
            if (in_array($value, self::PartnerValues)) {
                return $value;
            }
        }

        return "male";
    }

    public function prefersMale(Character $character): bool
    {
        return $this->getPreferredPartner($character) === "male";
    }

    public function prefersFemale(Character $character): bool
    {
        return $this->getPreferredPartner($character) === "female";
    }

    /**
     * @param FormExtensionEvent<Character> $event
     * @return void
     */
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
