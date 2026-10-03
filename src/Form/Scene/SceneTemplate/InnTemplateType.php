<?php
declare(strict_types=1);

namespace LotGD2\Form\Scene\SceneTemplate;

use LotGD2\Entity\DataObject\InnFlirtOption;
use LotGD2\Entity\DataObject\ValueRange;
use LotGD2\Form\CharacterExpressionType;
use LotGD2\Form\DataObject\InnFlirtOptionType;
use LotGD2\Form\GroupedFormType;
use LotGD2\Form\TypeProvidesDefaultDataInterface;
use LotGD2\Game\ExpressionService;
use LotGD2\Game\Scene\SceneTemplate\InnTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\ExpressionSyntax;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @phpstan-import-type InnTemplateConfiguration from InnTemplate
 * @implements TypeProvidesDefaultDataInterface<InnTemplateConfiguration>
 * @extends AbstractType<InnTemplateConfiguration>
 */
class InnTemplateType extends AbstractType implements TypeProvidesDefaultDataInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault("inherit_data", false);
        $resolver->setDefault("help", "The inn is a place with a barkeeper, a barmaid and a bard and offers
        charm-based interactions with the maid and the bard. Names and some actions are configurable.");
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultData = $this->getDefaultData();

        $builder
            ->add($builder
                ->create("inn", GroupedFormType::class, [
                    "inherit_data" => true,
                ])
                ->add("innName", TextType::class, [
                    "required" => true,
                    "label" => "Name of the inn",
                    "help" => <<<TXT
                        The name of the Inn. Can be used in texts to referr to the inn's name, but it will not 
                        change any actions.
                        TXT,
                    "data" => $defaultData["innName"],
                ])
            )

            ->add($builder
                ->create("innKeeper", GroupedFormType::class, [
                ])
                ->add("name", TextType::class, [
                    "required" => true,
                    "label" => "Name of the innkeeper",
                    "help" => <<<TXT
                        The name of the innkeeper. Can be used in texts to refer to the innkeeper's name with `innKeeper`, 
                        but it will not change any actions.
                        TXT,
                    "data" => $defaultData["innKeeper"]["name"],
                ])
                ->add("banter", TextType::class, [
                    "required" => true,
                    "label" => "Innkeeper banter",
                    "help" => <<<TXT
                        The innkeeper banter is the text that the innkeeper says to other patrons when the player enters 
                        the inn. This is a comma-separated list and the banter will be chosen at random.
                        TXT,
                    "data" => $defaultData["innKeeper"]["banter"],
                    "constraints" => [
                        new NotBlank(),
                    ]
                ])
                ->add("offerAlcohol", CheckboxType::class, [
                    "required" => false,
                    "label" => "Offer alcohol",
                    "help" => <<<TXT
                        Whether the innkeeper offers alcohol to the player.
                        TXT,
                    "data" => $defaultData["innKeeper"]["offerAlcohol"],
                ])
                ->add("alcoholPrice", CharacterExpressionType::class, [
                    "required" => true,
                    "label" => "Offer alcohol",
                    "help" => <<<TXT
                        Whether the innkeeper offers alcohol to the player.
                        TXT,
                    "data" => $defaultData["innKeeper"]["alcoholPrice"],
                    "constraints" => [
                        new NotBlank(),
                    ]
                ])
                ->add("drunkennessAmount", IntegerType::class, [
                    "required" => true,
                    "label" => "Alcohol amount",
                    "help" => <<<TXT
                        The amount of drunkenness increase the alcohol causes.
                        TXT,
                    "data" => $defaultData["innKeeper"]["drunkennessAmount"],
                    "constraints" => [
                        new NotBlank(),
                        new Positive(),
                    ]
                ])
                ->add("drunkennessLimit", IntegerType::class, [
                    "label" => "Drunkenness limit",
                    "help" => <<<TXT
                        The maximum amount of drunkenness player can have before the inn keeper refuses to serve alcohol.
                        TXT,
                    "data" => $defaultData["innKeeper"]["drunkennessLimit"],
                    "required" => true,
                    "constraints" => [
                        new NotBlank(),
                        new Positive(),
                    ]
                ])
                ->add($builder
                    ->create("texts", GroupedFormType::class, [

                    ])
                    ->add("intro", TextareaType::class, [
                        "required" => true,
                        "label" => "Intro text",
                        "help" => <<<TXT
                            The intro text is the text that the innkeeper says to the player when the player approaches
                            the barkeeper.
                            TXT,
                        "data" => $defaultData["innKeeper"]["texts"]["intro"],
                    ])
                    ->add("buyAlcohol", TextareaType::class, [
                        "required" => true,
                        "label" => "Buy alcohol text",
                        "help" => <<<TXT
                            Text that is displayed to the player when they buy alcohol. Additional context available herein
                            is price (the price of the alcohol), drunkenness (the character's drunkenness level) and 
                            maxDrunkenness (the maximum drunkenness level as configured in the scene's settings). Make 
                            sure to include messages about the drunkenness level (drunkenness > maxDrunkenness) and if
                            the player can actually afford the alcohol (gold >= price).
                            TXT,
                    ])
                )
            )

            ->add($builder
                ->create("femalePatron", GroupedFormType::class, [
                    "help" => <<<TXT
                        The female patron is traditionally Violet the barmaid. She can be the love interest of a 
                        character whose preferred partner is female.
                        TXT,
                ])
                ->add("name", TextType::class, [
                    "required" => true,
                    "label" => "Name",
                    "data" => $defaultData["femalePatron"]["name"],
                ])
                ->add("comment", TextType::class, [
                    "required" => true,
                    "label" => "Action upon entering",
                    "data" => $defaultData["femalePatron"]["comment"],
                    "help" => <<<TXT
                        This is a little description about what that person is doing. You can reference it in on text
                        within the inn with `patronBanter.female`.
                        TXT,
                ])

                ->add($builder
                    ->create("friend", GroupedFormType::class, [
                    ])
                    ->add("intro", TextareaType::class, [
                        "required" => true,
                        "label" => "Intro",
                        "help" => <<<TXT
                            This is the intro text for the patron when the character clicks on 'Chat with ...'
                            TXT,
                        "data" => $defaultData["femalePatron"]["friend"]["intro"],
                    ])
                    ->add("gossip", TextareaType::class, [
                        "required" => true,
                        "label" => "Gossip",
                        "help" => <<<TXT
                            This is the gossip text that is shown when the characters decided to go with gossip.
                            TXT,
                        "data" => $defaultData["femalePatron"]["friend"]["gossip"],
                    ])
                    ->add("judge", TextareaType::class, [
                        "required" => true,
                        "label" => "Judge",
                        "help" => <<<TXT
                            This is the text that is shown when the characters decided to go with judge. It is
                            supposed to show a different text depending on the characters charm level. The charm
                            can be accessed via the `charm` variable, and if tags are possible.
                            TXT,
                        "data" => $defaultData["femalePatron"]["friend"]["judge"],
                    ])
                )

                ->add($builder
                    ->create("flirt", GroupedFormType::class, [
                    ])
                    ->add("intro", TextareaType::class, [
                        "required" => true,
                        "label" => "Intro",
                        "help" => <<<TXT
                            If the character is interested in this patron, this text will be displayed instead
                            of the friend text.
                            TXT,
                        "data" => $defaultData["femalePatron"]["friend"]["intro"],
                    ])
                    ->add("hasSeenLoverMessage", TextareaType::class, [
                        "required" => true,
                        "label" => "Has seen lover message",
                        "help" => <<<TXT
                            This is the text that is displayed when the character has already seen 'their lover'.
                            TXT,
                        "data" => $defaultData["femalePatron"]["flirt"]["hasSeenLoverMessage"],
                    ])
                )

                ->add($builder
                    ->create("flirts", GroupedFormType::class, [
                        "inherit_data" => true,
                        "label" => "Flirt options",
                    ])
                    ->add("flirts", LiveCollectionType::class, [
                        "entry_type" => InnFlirtOptionType::class,
                        "allow_add" => true,
                        "allow_delete" => true,
                        "by_reference" => false,
                        "data" => $defaultData["femalePatron"]["flirts"],
                    ])
                )
            )

            ->add($builder
                ->create("malePatron", GroupedFormType::class, [
                    "help" => <<<TXT
                        The male patron is traditionally Seth the bard. He can be the love interest of a 
                        character whose preferred partner is male.
                        TXT,
                ])
                ->add("name", TextType::class, [
                    "required" => true,
                    "label" => "Name",
                    "data" => $defaultData["malePatron"]["name"],
                ])
                ->add("comment", TextType::class, [
                    "required" => true,
                    "label" => "Action upon entering",
                    "data" => $defaultData["malePatron"]["comment"],
                    "help" => <<<TXT
                        This is a little description about what that person is doing. You can reference it in on text
                        within the inn with `patronBanter.male`.
                        TXT,
                ])

                ->add($builder
                    ->create("friend", GroupedFormType::class, [
                    ])
                    ->add("intro", TextareaType::class, [
                        "required" => true,
                        "label" => "Intro",
                        "help" => <<<TXT
                            This is the intro text for the patron when the character clicks on 'Chat with ...'
                            TXT,
                        "data" => $defaultData["malePatron"]["friend"]["intro"],
                    ])
                    ->add("judge", TextareaType::class, [
                        "required" => true,
                        "label" => "Judge",
                        "help" => <<<TXT
                            This is the text that is shown when the characters decided to go with judge. It is
                            supposed to show a different text depending on the characters charm level. The charm
                            can be accessed via the `charm` variable, and if tags are possible.
                            TXT,
                        "data" => $defaultData["malePatron"]["friend"]["judge"],
                    ])
                )

                ->add($builder
                    ->create("flirt", GroupedFormType::class, [
                    ])
                    ->add("intro", TextareaType::class, [
                        "required" => true,
                        "label" => "Intro",
                        "help" => <<<TXT
                            If the character is interested in this patron, this text will be displayed instead
                            of the friend text.
                            TXT,
                        "data" => $defaultData["malePatron"]["friend"]["intro"],
                    ])
                    ->add("hasSeenLoverMessage", TextareaType::class, [
                        "required" => true,
                        "label" => "Has seen lover message",
                        "help" => <<<TXT
                            This is the text that is displayed when the character has already seen 'their lover'.
                            TXT,
                        "data" => $defaultData["malePatron"]["flirt"]["hasSeenLoverMessage"],
                    ])
                )

                ->add($builder
                    ->create("flirts", GroupedFormType::class, [
                        "inherit_data" => true,
                        "label" => "Flirt options",
                    ])
                    ->add("flirts", LiveCollectionType::class, [
                        "entry_type" => InnFlirtOptionType::class,
                        "allow_add" => true,
                        "allow_delete" => true,
                        "by_reference" => false,
                        "data" => $defaultData["malePatron"]["flirts"],
                    ])
                )
            )
        ;
    }

    /**
     * @return InnTemplateConfiguration
     */
    public function getDefaultData(): array
    {
        return [
            "innName" => "Boar's Head Inn",
            "minCharmPoints" => 0,
            "maxCharmPoints" => 25,
            "innKeeper" => [
                "name" => "Cedrik",
                "banter" => "dragons,Seth,Violet,MightyE,fine ales,Pegasus,craft ales",
                "offerAlcohol" => true,
                "alcoholPrice" => "character.level*10",
                "drunkennessAmount" => 33,
                "drunkennessLimit" => 66,
                "texts" => [
                    "intro" => /* @lang Twig */ <<<Twig
                        {{ innKeeper }} looks at you sort-of sideways like. He never was the sort who would trust a 
                        man any farther than he could throw them, which gave dwarves a decided advantage, except in 
                        provinces where dwarf tossing was made illegal. Cedrik polishes a glass, holds it up to the light 
                        of the door as another patron opens it to stagger out in to the street. He then makes a face, 
                        spits on the glass and goes back to polishing it. <<What d'ya want?>>, he asks gruffly.
                        Twig,
                    "buyAlcohol" => /* @lang Twig */ <<<Twig
                        {% if drunkenness > maxDrunkenness %}
                            Pounding your fist on the bar, you demand an ale but {{ innKeeper }} continues to clean 
                            the glass he was working on. <<You've had enough 
                            {{ gender.pronouns == "male" ? "lad" : (gender.pronouns == "female" ? "lass" : "kid") }}>>,
                            he declares.
                        {% elseif gold >= price%}
                            Pounding your fist on the bar, you demand an ale. {{ innKeeper }} pulls out a glass, 
                            and pours a foamy ale from a tapped barrel behind him. He slides it down the bar, and you 
                            catch it with your warrior-like reflexes.
                            
                            Turning around, you take a big chug of the hearty draught, and give
                            {{ gender.partner == "male" ? patron.male : patron.female }} an ale-foam mustache smile.
                        {% else %}
                            Pounding your fist on the bar, you demand an ale and put {{ gold }} gold on the table.
                            {{ innKeeper }} looks at you and shares his head. <<That's not nearly enough,
                            {{ gender.pronouns == "male" ? "lad" : (gender.pronouns == "female" ? "lass" : "kid") }}>>.
                        {% endif %}
                        Twig,
                ],
            ],
            "malePatron" => [
                "name" => "Seth",
                "comment" => "who is tuning his harp by the fire",

                "friend" => [
                    "intro" => /* @lang Twig */ <<<Twig
                        {{ patron.male }} looks at you expectantly.
                        Twig,
                    "judge" => /* @lang Twig */ <<<Twig
                        {{ patron.male }} looks you up and down very seriously. Only a friend can be truly honest, and that is why 
                        you asked him. Finally she reaches a conclusion and states
                        {% if charm <= 0 %}
                            <<You make me glad I'm not not into you!>>
                        {% elseif charm <= 3 %}
                            <<I've seen some handsome people in my day, but I'm afraid you aren't one of them.>>
                        {% elseif charm <= 6 %}
                            <<I've seen worse my friend, but only trailing a horse.>>
                        {% elseif charm <= 9 %}
                            <<You're of fairly average appearance, my friend.>>
                        {% elseif charm <= 12 %}
                            <<You certainly are something to look at, just don't get too big of a head about it, eh?>>
                        {% elseif charm <= 15 %}
                            <<You're quite a bit better than average!>>
                        {% elseif charm <= 18 %}
                            <<Few people would be able to resist you!>>
                        {% else %}
                            <<I hate you, why, you are simply the most beautiful person ever!>>
                        {% endif %}
                        Twig,
                ],
                "flirt" => [
                    "intro" => /* @lang Twig */ <<<Twig
                        {{ patron.male }} looks at you expectantly.
                        Twig,
                    "hasSeenLoverMessage" => /* @lang Twig */ <<<Twig
                        You think you had better not push your luck with {{ patron.male }} today.
                        Twig,
                ],
                "flirts" => [
                    new InnFlirtOption(
                        charmRequirement: 2,
                        name: "Wink",
                        successMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} grins a big toothy grin.  My, isn't the dimple in his chin cute??
                            Twig,
                        failureMessage:  <<<Twig
                            {{ patron.male }} raises an eyebrow at you and asks if you have something in your eye.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 2),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 4,
                        name: "Flutter eyelashes",
                        successMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} smiles at you and says, <<My, what pretty eyes you have.>>
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} smiles, and waves... to the person standing behind you.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 6),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 7,
                        name: "Drop Hanky",
                        successMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} bends over and retrieves your hanky, while you admire his firm posterior.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} bends over and retrieves your hanky, wipes his nose with it, and gives 
                            it back.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 9),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 11,
                        name: "Ask him to buy you a drink",
                        successMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} places his arm around your waist, and escorts you to the bar where he buys 
                            you one of the Inn's fine swills.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            {{ patron.male }} apologizes, <<I'm sorry, I have no money to spare>> as he turns out his 
                            moth-riddled pocket.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 13),
                        charmRemovalRange: new ValueRange(0, 10),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 14,
                        name: "Kiss him soundly",
                        successMessage: /* @lang Twig */ <<<Twig
                            You walk up to {{ patron.male }}, grab him by the shirt, pull him to his feet, and plant 
                            a firm, long kiss right on his handsome lips. He collapses after, hair a bit disheveled, 
                            and short on breath.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            You duck down to kiss {{ patron.male }} on the lips, but just as you do so, he bends over to tie his shoe.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 17),
                        charmRemovalRange: new ValueRange(0, 13),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 18,
                        name: "Completely seduce the bard",
                        successMessage: /* @lang Twig */ <<<Twig
                            Standing at the base of the stairs, you make a come-hither gesture at {{ patron.male }}. He 
                            follows you like a puppy-dog.
                            
                            {% if consumedTurns > 0 %}
                            You feel exhausted!
                            {% endif %}
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            <<I'm sorry, but I have a show in 5 minutes>>
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: true,
                        charmRewardRange: new ValueRange(null, 25),
                        charmRemovalRange: new ValueRange(0, 17),
                    ),
                ],
            ],
            "femalePatron" => [
                "name" => "Violet",
                "comment" => "who is serving ale to some locals",
                "friend" => [
                    "intro" => /* @lang Twig */ <<<Twig
                        You go over to {{ patron.female }} and help her with the ales she is carrying. Once they are passed out,
                        she takes a cloth and wipes the sweat off of her brow, thanking you much. Of course you didn't 
                        mind, as she is one of your oldest and truest friends!
                        Twig,
                    "gossip" => /* @lang Twig */ <<<Twig
                        You and {{ patron.female }} gossip quietly for a few minutes about not much at all.
                        She offers you a pickle. ou accept, knowing that it's in her nature to do so as a former 
                        pickle wench. After a few minutes, {{ innKeeper }} begins to cast burning looks your way, and you 
                        decide you had best let {{ patron.female }} get back to work.
                        Twig,
                    "judge" => /* @lang Twig */ <<<Twig
                        {{ patron.female }} looks you up and down very seriously. Only a friend can be truly honest, and that is why 
                        you asked her. Finally she reaches a conclusion and states
                        {% if charm <= 0 %}
                            <<Your outfit doesn't leave much to the imagination, but some things are best not thought 
                            about at all!  Get some less revealing clothes as a public service!>>
                        {% elseif charm <= 3 %}
                            <<I've seen some lovely people in my day, but I'm afraid you aren't one of them.>>
                        {% elseif charm <= 6 %}
                            <<I've seen worse my friend, but only trailing a horse.>>
                        {% elseif charm <= 9 %}
                            <<You're of fairly average appearance my friend.>>
                        {% elseif charm <= 12 %}
                            <<You certainly are something to look at, just don't get too big of a head about it, eh?>>
                        {% elseif charm <= 15 %}
                            <<You're quite a bit better than average!>>
                        {% elseif charm <= 18 %}
                            <<Few people could count themselves to be in competition with you!>>
                        {% else %}
                            <<I hate you, why, you are simply the most beautiful person ever!>>
                        {% endif %}
                        Twig,
                ],
                "flirt" => [
                    "intro" => /* @lang Twig */ <<<Twig
                        You stare dreamily across the room at {{ patron.female }}, who leans across a table to serve a 
                        patron a drink. In doing so, she shows perhaps a bit more skin than is necessary, but you don't 
                        feel the need to object.
                        Twig,
                    "hasSeenLoverMessage" => /* @lang Twig */ <<<Twig
                        You think you had better not push your luck with {{ patron.female }} today.
                        Twig,
                ],
                "flirts" => [
                    new InnFlirtOption(
                        charmRequirement: 2,
                        name: "Wink",
                        successMessage: /* @lang Twig */ <<<Twig
                            You wink at {{ patron.female }}, and she gives you a warm smile in return.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            You wink at {{ patron.female }}, but she pretends not to notice.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 3),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 4,
                        name: "Kiss her hand",
                        successMessage: /* @lang Twig */ <<<Twig
                            You stroll confidently across the room toward {{ patron.female }}. Taking hold of her hand, 
                            you kiss it gently, your lips remaining for only a few seconds. {{ patron.female }} blushes 
                            and tucks a strand of hair behind her ear as you walk away, then presses the back side of 
                            her hand longingly against her cheek while watching your retreat.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            You stroll confidently across the room toward {{ patron.female }}. You reach out to grab her 
                            hand, but {{ patron.female }} takes her hand back and asks if perhaps you'd like a drink.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 6),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 7,
                        name: "Peck her on the lips",
                        successMessage: /* @lang Twig */ <<<Twig
                            Standing with your back against a wooden column, you wait for {{ patron.female }} to wander 
                            your way when you call her name. She approaches, a hint of a smile on her face. You grab her 
                            chin, lift it slightly, and place a firm but quick kiss on her plump lips.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            Standing with your back against a wooden column, you wait for {{ patron.female }} to wander 
                            your way when you call her name. She smiles and apologizes, insisting that she is simply too 
                            busy to take a moment from her work.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: false,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 10),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 11,
                        name: "Set her on your lap",
                        successMessage: /* @lang Twig */ <<<Twig
                            Sitting at a table, you wait for {{ patron.female }} to come your way. When she does so, 
                            you reach up and grab her firmly by the waist, pulling her down on to your lap. She laughs 
                            and throws her arms around your neck in a warm hug before thumping you on the chest, 
                            standing up, and insisting that she really must get back to work.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            Sitting at a table, you wait for {{ patron.female }} to come your way. When she does so, 
                            you reach up to grab her by the waist, but she deftly dodges, careful not to spill the 
                            drink that she's carrying.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 13),
                        charmRemovalRange: new ValueRange(0, 10),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 14,
                        name: "Grab her backside",
                        successMessage: /* @lang Twig */ <<<Twig
                            Waiting for {{ patron.female }} to brush by you, you firmly palm her backside. She turns 
                            and gives you a warm, knowing smile.
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            Waiting for {{ patron.female }} to brush by you, you firmly palm her backside. She turns 
                            and slaps you across the face. Hard. Perhaps you should go a little slower.
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: false,
                        charmRewardRange: new ValueRange(null, 17),
                        charmRemovalRange: new ValueRange(0, 13),
                    ),
                    new InnFlirtOption(
                        charmRequirement: 18,
                        name: "Carry her upstairs",
                        successMessage: /* @lang Twig */ <<<Twig
                            Like a whirlwind, you sweep through the inn, grabbing {{ patron.female }}, who throws her 
                            arms around your neck, and whisk her upstairs to her room there. Not more than 10 minutes 
                            later you stroll down the stairs, smoking a pipe, and grinning from ear to ear.
                            
                            {% if consumedTurns > 0 %}
                            You feel exhausted!
                            {% endif %}
                            Twig,
                        failureMessage: /* @lang Twig */ <<<Twig
                            Like a whirlwind, you sweep through the inn, and grab for {{ patron.female }}. She turns 
                            and slaps your face! <<What sort of girl do you think I am, anyhow?>> she demands!
                            Twig,
                        addCharmIfSuccessful: true,
                        removeCharmIfFailure: true,
                        isExhaustiveIfSuccessful: true,
                        charmRewardRange: new ValueRange(null, 25),
                        charmRemovalRange: new ValueRange(0, 17),
                    ),
                ],
            ],
        ];
    }
}