<?php
declare(strict_types=1);

namespace LotGD2\Twig\Component\User;

use Doctrine\ORM\EntityManagerInterface;
use LotGD2\Entity\Mapped\Character;
use LotGD2\Form\CharacterType;
use LotGD2\Game\Character\CharacterService;
use LotGD2\Game\Character\CharacterTitleService;
use LotGD2\Twig\Component\ComponentWithSaveStatusTrait;
use LotGD2\Twig\Component\ModalFormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(template: "component/Form/ModalForm.html.twig")]
class CharacterForm extends AbstractController implements ModalFormInterface
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveCollectionTrait;
    use ComponentToolsTrait;
    use ComponentWithSaveStatusTrait {
        ComponentWithSaveStatusTrait::__invoke insteadof DefaultActionTrait;
    }

    #[LiveProp]
    public ?string $key;

    #[LiveProp(fieldName: "somethingElse")]
    public ?Character $entity = null;

    #[LiveProp]
    public bool $new = true;

    public string $entityName {
        get => "character";
    }

    #[LiveAction]
    public function save(
        EntityManagerInterface $entityManager,
        CharacterService $characterService,
        CharacterTitleService $titleService,
    ): void {
        $this->submitForm();

        /** @var Character $character */
        $character = $this->getForm()->getData();

        if ($this->new) {
            $characterService->newCharacter($character);
        }

        $characterId = $character->id;

        $entityManager->persist($character);
        $entityManager->flush();

        if (!$characterId) {
            $this->resetForm();
            $this->new = false;
        }

        $this->emitUp("characterAdded", ["character" => $character->id]);
        $this->saved = true;
    }

    /**
     * @return FormInterface<Character>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CharacterType::class, $this->entity, options: [
            "new" => $this->new,
        ]);
    }
}
