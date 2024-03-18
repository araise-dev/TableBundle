<?php

declare(strict_types=1);

use araise\CoreBundle\araiseCoreBundle;
use araise\SearchBundle\araiseSearchBundle;
use araise\TableBundle\araiseTableBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;
use whatwedo\TwigBootstrapIcons\whatwedoTwigBootstrapIconsBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

return [
    FrameworkBundle::class => [
        'all' => true,
    ],
    DoctrineBundle::class => [
        'all' => true,
    ],
    TwigBundle::class => [
        'all' => true,
    ],
    ZenstruckFoundryBundle::class => [
        'all' => true,
    ],
    araiseCoreBundle::class => [
        'all' => true,
    ],
    araiseTableBundle::class => [
        'all' => true,
    ],
    araiseSearchBundle::class => [
        'all' => true,
    ],
    WebpackEncoreBundle::class => [
        'all' => true,
    ],
    whatwedoTwigBootstrapIconsBundle::class => [
        'all' => true,
    ],
    SecurityBundle::class => [
        'all' => true,
    ],
    Symfony\UX\StimulusBundle\StimulusBundle::class => [
        'all' => true,
    ],
];
