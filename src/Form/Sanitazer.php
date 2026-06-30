<?php

namespace App\Form;

use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;
use function is_string;
use function strip_tags;

class Sanitazer
{
    /**
     * @param HTMLPurifiersRegistryInterface $purifier
     */
    public function __construct(private readonly HTMLPurifiersRegistryInterface $purifier)
    {
    }

    /**
     * @param array $data
     * @param array $fieldsConfig
     * @return array
     */
    public function sanitaze(array $data, array $fieldsConfig): array
    {
        $purifier = $this->purifier->get('default');

        foreach ($fieldsConfig as $field => $options) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                continue;
            }

            $value = $purifier->purify($data[$field]);

            if (($options['strip_tags'] ?? false) === true) {
                $value = strip_tags($value);
            }

            $data[$field] = $value;
        }

        return $data;
    }
}
