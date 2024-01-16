<?php

declare(strict_types=1);
/*
 * Copyright (c) 2024, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace araise\TableBundle\Filter\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterOperatorDto
{
    public const OPT_HAS_FILTER_VALUE = 'has_filter_value';

    public function __construct(
        public readonly string $messageKey,
        private array $options = []
    ) {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            self::OPT_HAS_FILTER_VALUE => true,
        ]);
        $this->options = $resolver->resolve($options);
    }

    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOption(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function __toString(): string
    {
        return $this->messageKey;
    }
}
