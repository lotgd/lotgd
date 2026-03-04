<?php
declare(strict_types=1);

namespace LotGD2\Entity;

/**
 * Paragraph container.
 *
 * Contains text and the corresponding context with additional arguments that can
 * be added to text and could be rendered alongside.
 */
class Paragraph
{
    public function __construct(
        public string $id {
            get => $this->id;
        },
        public string $text {
            get => $this->text;
        },
        /**
         * @var array<string, mixed>
         */
        public array $context = [] {
            get => $this->context;
            set => $value;
        },
        public bool $translated = false {
            get => $this->translated;
        },
        /**
         * @var array<string, mixed>
         */
        public array $kwargs = [] {
            get => $this->kwargs;
        }
    ) {

    }

    public function addContext(string $key, mixed $value): self {
        $this->context = array_merge($this->context, [$key => $value]);
        return $this;
    }
}
