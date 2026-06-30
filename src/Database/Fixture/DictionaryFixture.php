<?php

declare(strict_types=1);

namespace App\Database\Fixture;

use App\Database\Entity\Dictionary;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Application\ApplicationTypeEnum;
use App\Enum\Dictionary\DictionaryNameEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Class DictionaryFixture
 * @package App\Database\Fixture
 */
class DictionaryFixture extends Fixture implements OrderedFixtureInterface
{
    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'name' => DictionaryNameEnum::TYPY_WNIOSKOW,
                'items' => [
                    [
                        'hidden_value' => 'educational-process',
                        'secondary_value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                        'value' => ApplicationTypeEnum::EDUCATIONAL_PROCESS->value,
                        'value_key' => 'adaptacji procesu kształcenia'
                    ],
                    [
                        'hidden_value' => 'language-interpreter',
                        'secondary_value' => 'Fusce ut vulputate risus, eget cursus erat.',
                        'value' => ApplicationTypeEnum::LANGUAGE_INTERPRETER->value,
                        'valueKey' => 'przyznanie tłumacza języka migowego'
                    ],
                    [
                        'hidden_value' => 'specialised-equipment',
                        'secondary_value' => 'Curabitur convallis est nec nulla porta, sed sollicitudin diam vestibulum. Nunc sed enim pharetra, hendrerit lectus eu, sodales eros.',
                        'value' => ApplicationTypeEnum::SPECIALISED_EQUIPMENT->value,
                        'valueKey' => 'wypożyczenie sprzętu specjalistycznego'
                    ],
                    [
                        'hidden_value' => 'teaching-assistant',
                        'secondary_value' => 'Donec ac augue et dolor aliquet euismod. Quisque eu congue nulla. Vivamus tempus ante vitae vestibulum commodo. Sed et blandit libero.',
                        'value' => ApplicationTypeEnum::TEACHING_ASSISTANT->value,
                        'value_key' => 'organizację wsparcia asystenta dydaktycznego'
                    ],
                ]
            ],
            [
                'name' => DictionaryNameEnum::RODZAJE_ADAPTACJI,
                'items' => [
                    [
                        'value' => 'Adaptacja 1',
                    ],
                    [
                        'value' => 'Adaptacja 2',
                    ],
                ]
            ],
            [
                'name' => DictionaryNameEnum::TRYB_STUDIOW,
                'items' => [
                    [
                        'value' => 'Stacjonarny',
                    ],
                    [
                        'value' => 'Niestacjonarny',
                    ],
                ]
            ],
            [
                'name' => DictionaryNameEnum::SPECJALISCI,
                'items' => [
                    [
                        'value' => 'Tomasz Kowalczyk',
                        'value_key' => 't.kowalczyk@vizja.pl'
                    ],
                    [
                        'value' => 'Artur Nowak',
                        'value_key' => 'a.nowak@vizja.pl'
                    ],
                ]
            ],
            [
                'name' => DictionaryNameEnum::ROK_STUDIOW,
                'items' => [
                    [
                        'value' => '2025/2026',
                    ],
                ]
            ],
            [
                'name' => DictionaryNameEnum::WYDZIAL,
                'items' => [
                    [
                        'value' => 'Technologii Informatycznych',
                        'value_key' => 'Jan Kowalski',
                    ],
                ]
            ],
            [
                'name' => DictionaryNameEnum::KIERUNEK_STUDIOW,
                'items' => [
                    [
                        'value' => 'Informatyka',
                    ],
                ]
            ],

            [
                'name' => DictionaryNameEnum::SPRZET,
                'items' => [
                    [
                        'value' => 'Lupa',
                    ],
                    [
                        'value' => 'Klawiatura',
                    ],
                ]
            ],

            [
                'name' => DictionaryNameEnum::PROSBA,
                'items' => [
                    [
                        'value' => 'Zwracam się z prośbą Przyznanie wsparcia tłumacza języka migowego  w roku akademickim',
                    ],
                ]
            ],

            [
                'name' => DictionaryNameEnum::PRZYZNANIE_KIEDY,
                'items' => [
                    [
                        'value' => 'Podczas obrony pracy dyplomowej',
                    ],
                    [
                        'value' => 'Podczas sesji egzaminacyjnej',
                    ],
                    [
                        'value' => 'Podczas zajęć stacjonarnych/online',
                    ],
                ]
            ]
        ];

        foreach ($data as $dictionary) {
            $dictionaryEntity = new Dictionary();
            $dictionaryEntity->name = $dictionary['name'];

            $manager->persist($dictionaryEntity);

            foreach ($dictionary['items'] as $item) {
                $dictionaryItemEntity = new Item();
                $dictionaryItemEntity->dictionary = $dictionaryEntity;
                $dictionaryItemEntity->value = $item['value'];

                if (isset($item['hidden_value'])) {
                    $dictionaryItemEntity->hiddenValue = $item['hidden_value'];
                }

                if (isset($item['secondaryValue'])) {
                    $dictionaryItemEntity->secondaryValue = $item['secondary_value'];
                }

                $manager->persist($dictionaryItemEntity);
            }
        }

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return 10;
    }
}
