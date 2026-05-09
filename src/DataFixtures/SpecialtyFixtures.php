<?php
declare(strict_types=1);

namespace LotGD2\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use LotGD2\Entity\Battle\ProtoBuff;
use LotGD2\Entity\Character\SpecialtySkill;
use LotGD2\Entity\Mapped\Race;
use LotGD2\Entity\Mapped\Specialty;
use LotGD2\Game\Race\StandardRace;

class SpecialtyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $specialties = [
            new Specialty(
                name: "Dark Arts",
                description: <<<TEXT
                    Killing a lot of woodland creatures (Dark Arts)
                    TEXT,
                selectionText: <<<TXT
                    Growing up, you recall killing many small woodland creatures, insisting that they were plotting 
                    against you. Your parents, concerned that you had taken to killing the creatures barehanded, bought 
                    you your very first pointy twig. It wasn't until your teenage years that you began performing dark 
                    rituals with the creatures, disappearing into the forest for days on end, no one quite knowing where 
                    those sounds came from.
                    TXT,
                className: null,
                skills: [
                    new SpecialtySkill(
                        name: "Skeleton Crew",
                        costs: 1,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.darkArts.1",
                            name: "Skeleton Crew",
                            activatesAt: ProtoBuff::ACTIVATES_ON_ROUNDSTART,
                            rounds: 5,
                            startMessage: "You call on the spirits of the dead, and skeletal hands claw at {{ badGuy.name }} from beyond the grave.",
                            endMessage: "Your skeleton minions crumble to dust",
                            effectSuccessMessage: "An undead minion hits {{ badGuy.name }} for {{ damage }} damage.",
                            noEffectMessage: "An undead minion tries to hit {{ badGuy.name }} but MISSES.",
                            numberOfMinions: "character.level/3  + 1",
                            minionMaxBadGuyDamage: "character.level/2 + 1",
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Voodoo",
                        costs: 2,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.darkArts.2",
                            name: "Voodoo",
                            activatesAt: ProtoBuff::ACTIVATES_ON_ROUNDSTART,
                            rounds: 1,
                            startMessage: "You pull out a tiny doll that looks like {{ badGuy.name }}.",
                            effectSuccessMessage: "You thrust a pin into the {{ badGuy.name }} doll hurting it for {{ damage }} points!",
                            numberOfMinions: 1,
                            minionMinBadGuyDamage: "stats.attack * 1.5",
                            minionMaxBadGuyDamage: "stats.attack * 3",
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Curse Spirit",
                        costs: 3,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.darkArts.3",
                            name: "Curse Spirit",
                            activatesAt: ProtoBuff::ACTIVATES_ON_DEFENSE_TURN,
                            rounds: 5,
                            startMessage: "You place a curse on {{ badGuy.name }}'s ancestors.",
                            roundMessage: "{{ badGuy.name}} staggers under the weight of your curse, and deals only half damage.",
                            endMessage: "Your curse has faded.",
                            badGuyDamageModifier: 0.5,
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Wither Soul",
                        costs: 5,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.darkArts.4",
                            name: "Wither Soul",
                            activatesAt: ProtoBuff::ACTIVATES_ON_BOTH_TURNS,
                            rounds: 5,
                            startMessage: "You hold out your hand and {{ badGuy.name }} begins to bleed from their ears.",
                            roundMessage: "{{ badGuy.name }} claws at their eyes, trying to release their own soul, and cannot attack or defend.",
                            endMessage: "Your victim's soul has been restored.",
                            badGuyDamageModifier: 0.,
                            badGuyAttackModifier: 0.,
                        ),
                    ),
                ],
                configuration: [
                ],
            ),
            new Specialty(
                name: "Mystical Powers",
                description: <<<TEXT
                    Dabbling in mystical forces (Mystical Powers)
                    TEXT,
                selectionText: <<<TXT
                    Growing up, you remember knowing there was more to the world than the physical, and what you could 
                    place your hands on. You realized that your mind itself, with training, could be turned in to a 
                    weapon. Over time, you began to control the thoughts of small creatures, commanding them to do your 
                    bidding, and also to begin to tap in to the mystical force known as mana, which could be shaped in 
                    to numerous elemental forms, fire, water, ice, earth, wind, and also used as a weapon against your foes.
                    TXT,
                className: null,
                skills: [
                    new SpecialtySkill(
                        name: "Regeneration",
                        costs: 1,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.mysticalPowers.1",
                            name: "Regeneration",
                            activatesAt: ProtoBuff::ACTIVATES_ON_ROUNDSTART,
                            rounds: 5,
                            startMessage: "You begin to regenerate!",
                            endMessage: "You have stopped regenerating.",
                            effectSuccessMessage: "You regenerate for {{ damage }} health.",
                            noEffectMessage: "You have no wounds to regenerate.",
                            goodGuyRegeneration: "character.level",
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Earth Fist",
                        costs: 2,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.mysticalPowers.2",
                            name: "Earth Fist",
                            activatesAt: ProtoBuff::ACTIVATES_ON_ROUNDSTART,
                            rounds: 5,
                            startMessage: "{{ badGuy.name }} is clutched by a fist of earth and slammed to the ground!",
                            endMessage: "The earthen fist crumbles to dust.",
                            effectSuccessMessage: "A huge fist of earth pummels {{ badGuy.name }} for {{ damage }} points.",
                            numberOfMinions: 1,
                            minionMinBadGuyDamage: 1,
                            minionMaxBadGuyDamage: "character.level * 3",
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Siphon Life",
                        costs: 3,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.mysticalPowers.3",
                            name: "Siphon Life",
                            activatesAt: ProtoBuff::ACTIVATES_ON_BOTH_TURNS,
                            rounds: 5,
                            startMessage: "Your weapon glows with an unearthly presence.",
                            endMessage: "Your weapon's aura fades.",
                            effectSuccessMessage: "You are healed for {{ heal }} health.",
                            effectFailsMessage: "Your weapon wails as you deal no damage to your opponent.",
                            noEffectMessage: "You feel a tingle as your weapon tries to heal your effectively healthy body.",
                            badGuyLifeTap: 1.,
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Lightning Aura",
                        costs: 5,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.mysticalPowers.4",
                            name: "Lightning Aura",
                            activatesAt: ProtoBuff::ACTIVATES_ON_BOTH_TURNS,
                            rounds: 5,
                            startMessage: "Your skin sparkles as you assume an aura of lightning.",
                            endMessage: "With a fizzle, your skin returns to normal.",
                            effectSuccessMessage: "{{ badGuy.name }} recoils as lighning arcs out from your skin, hitting for {{ reflectedDamage }} damage.",
                            effectFailsMessage: "{{ badGuy.name }} is slightly singed by your lightning, but otherwise unharmed.",
                            noEffectMessage: "{{ badGuy.name }} is slightly singed by your lightning, but otherwise unharmed.",
                            goodGuyDamageReflection: 2.,
                        ),
                    ),
                ],
                configuration: [
                ],
            ),
            new Specialty(
                name: "Thievery",
                description: <<<TEXT
                    Stealing from the rich and giving to yourself (Thievery)
                    TEXT,
                selectionText: <<<TXT
                    Growing up, you recall discovering that a casual bump in a crowded room could earn you the coin 
                    purse of someone otherwise more fortunate than you.  You also discovered that the back side of your 
                    enemies were considerably more prone to a narrow blade than the front side was to even a powerful weapon.
                    TXT,
                className: null,
                skills: [
                    new SpecialtySkill(
                        name: "Insult",
                        costs: 1,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.thievery.1",
                            name: "Insult",
                            activatesAt: ProtoBuff::ACTIVATES_ON_DEFENSE_TURN,
                            rounds: 5,
                            startMessage: "You call {{ badGuy.name }} a bad name, making them cry.",
                            roundMessage: "{{ badGuy.name }} feels dejected and cannot attack as well.",
                            endMessage: "Your victim stops crying and wipes its nose.",
                            badGuyDamageModifier: 0.5,
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Poison Blade",
                        costs: 2,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.thievery.2",
                            name: "Poison Blade",
                            activatesAt: ProtoBuff::ACTIVATES_ON_OFFENSE_TURN,
                            rounds: 5,
                            startMessage: "You apply some poison to your {{ goodGuy.weapon }} ",
                            roundMessage: "Your attack is multiplied!",
                            endMessage: "Your victim's blood has washed the poison from your blade.",
                            goodGuyAttackModifier: 2.,
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Hidden Attack",
                        costs: 3,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.thievery.3",
                            name: "Hidden Attack",
                            activatesAt: ProtoBuff::ACTIVATES_ON_DEFENSE_TURN,
                            rounds: 5,
                            startMessage: "With the skill of an expert thief, you virtually dissapear, and attack {{ badGuy.name }} from a safer vantage point.",
                            roundMessage: "{{ badGuy.name}} cannot locate you.",
                            endMessage: "Your victim has located you.",
                            badGuyAttackModifier: 0.,
                        ),
                    ),
                    new SpecialtySkill(
                        name: "Backstab",
                        costs: 5,
                        buff: new ProtoBuff(
                            id: "lotgd2.specialties.thievery.4",
                            name: "Backstab",
                            activatesAt: ProtoBuff::ACTIVATES_ON_BOTH_TURNS,
                            rounds: 5,
                            startMessage: "Using your skills as a thief, dissapear behind {{ badGuy.name }} and slide a thin blade between its vertibrae!",
                            roundMessage: "Your attack is multiplied, as is your defense!",
                            endMessage: "Your victim won't be so likely to let you get behind it again!",
                            goodGuyAttackModifier: 3.,
                            goodGuyDefenseModifier: 3.,
                        )
                    ),
                ],
                configuration: [
                ],
            ),
        ];

        array_map(fn (Specialty $specialty) => $manager->persist($specialty), $specialties);
        $manager->flush();
    }
}