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
 * Type
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Type implements \JsonSerializable
{
    public const TYPE_STRUCT = 'struct';
    public const TYPE_MAP = 'map';
    public const TYPE_ARRAY = 'array';

    private ?string $name;
    private ?string $type;
    private ?string $description;
    private ?bool $deprecated;
    private ?string $parent;
    private ?bool $base;
    /**
     * @var array<Property>
     */
    private array $properties;
    private ?string $discriminator;
    private ?array $mapping;
    private ?array $template;
    private ?string $reference;

    public function __construct(array $type)
    {
        $type = $this->bcLayer($type);

        $this->name = $type['name'] ?? null;
        $this->type = $type['type'] ?? null;
        $this->description = $type['description'] ?? null;
        $this->deprecated = $type['deprecated'] ?? null;
        $this->parent = $type['parent'] ?? null;
        $this->base = $type['base'] ?? null;
        $this->properties = $this->convertProperties($type['properties'] ?? null);
        $this->discriminator = $type['discriminator'] ?? null;
        $this->mapping = isset($type['mapping']) ? (array) $type['mapping'] : null;
        $this->template = isset($type['template']) ? (array) $type['template'] : null;
        $this->reference = $type['reference'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function setDeprecated(?bool $deprecated): void
    {
        $this->deprecated = $deprecated;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(?string $parent): void
    {
        $this->parent = $parent;
    }

    public function getBase(): ?bool
    {
        return $this->base;
    }

    public function setBase(?bool $base): void
    {
        $this->base = $base;
    }

    /**
     * @return array<Property>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getProperty(int $index): ?Property
    {
        return $this->properties[$index] ?? null;
    }

    public function indexOf(string $propertyName): ?int
    {
        foreach ($this->properties as $index => $property) {
            if ($property->getName() === $propertyName) {
                return $index;
            }
        }

        return null;
    }

    public function getDiscriminator(): ?string
    {
        return $this->discriminator;
    }

    public function setDiscriminator(?string $discriminator): void
    {
        $this->discriminator = $discriminator;
    }

    public function getMapping(): ?array
    {
        return $this->mapping;
    }

    public function setMapping(?array $mapping): void
    {
        $this->mapping = $mapping;
    }

    public function getTemplate(): ?array
    {
        return $this->template;
    }

    public function setTemplate(?array $template): void
    {
        $this->template = $template;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'deprecated' => $this->deprecated,
            'parent' => $this->parent,
            'base' => $this->base,
            'properties' => $this->properties,
            'discriminator' => $this->discriminator,
            'mapping' => $this->mapping,
            'template' => $this->template,
            'reference' => $this->reference,
        ], function ($value) {
            return $value !== null;
        });
    }

    private function convertProperties(?array $properties): array
    {
        if ($properties === null) {
            return [];
        }

        $result = [];
        foreach ($properties as $property) {
            if ($property instanceof \stdClass) {
                $result[] = new Property((array) $property);
            } elseif (is_array($property)) {
                $result[] = new Property($property);
            }
        }

        return $result;
    }

    private function bcLayer(array $type): array
    {
        if (isset($type['template']) && is_string($type['template'])) {
            $type['template'] = ['T' => $type['template']];
        }

        if (isset($type['type']) && $type['type'] === 'object') {
            $type['type'] = 'struct';
        }

        if (isset($type['type']) && $type['type'] === 'reference') {
            $type['type'] = 'struct';
            if (isset($type['ref'])) {
                $type['parent'] = $type['ref'];
                unset($type['ref']);
            }
        } else {
            if (isset($type['ref'])) {
                $type['reference'] = $type['ref'];
                unset($type['ref']);
            }
        }

        return $type;
    }
}
