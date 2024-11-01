<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright 2010-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TypeAPI\Editor\Model;

/**
 * Property
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Property implements \JsonSerializable
{
    public const TYPE_OBJECT = 'object';
    public const TYPE_MAP = 'map';
    public const TYPE_ARRAY = 'array';
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ANY = 'any';
    public const TYPE_GENERIC = 'generic';

    private ?string $name;
    private ?string $description;
    private ?string $type;
    private ?string $format;
    private ?bool $deprecated;
    private ?string $reference;
    private ?string $generic;
    private ?array $template;

    public function __construct(array $property)
    {
        $property = $this->byLayer($property);

        $this->name = $property['name'] ?? null;
        $this->description = $property['description'] ?? null;
        $this->type = $property['type'] ?? null;
        $this->format = $property['format'] ?? null;
        $this->deprecated = $property['deprecated'] ?? null;
        $this->reference = $property['reference'] ?? null;
        $this->generic = $property['generic'] ?? null;
        $this->template = isset($property['template']) ? (array) $property['template'] : null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    public function setDeprecated(?bool $deprecated): void
    {
        $this->deprecated = $deprecated;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function getGeneric(): ?string
    {
        return $this->generic;
    }

    public function setGeneric(?string $generic): void
    {
        $this->generic = $generic;
    }

    public function getTemplate(): ?array
    {
        return $this->template;
    }

    public function setTemplate(?array $template): void
    {
        $this->template = $template;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'format' => $this->format,
            'deprecated' => $this->deprecated,
            'reference' => $this->reference,
            'generic' => $this->generic,
            'template' => $this->template,
        ], function ($value) {
            return $value !== null;
        });
    }

    private function byLayer(array $property): array
    {
        if (isset($property['refs']) && is_array($property['refs']) && count($property['refs']) > 0) {
            if ($property['refs'][0] === 'T') {
                if ($property['type'] === 'map' || $property['type'] === 'array') {
                    $property['reference'] = 'generic';
                    $property['generic'] = 'T';
                }
            } else {
                $property['reference'] = $property['refs'][0];
            }
        }

        return $property;
    }
}
