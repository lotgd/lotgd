<?php
declare(strict_types=1);

namespace LotGD2\Game\Handler;

use LotGD2\Entity\Mapped\Character;

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
}
