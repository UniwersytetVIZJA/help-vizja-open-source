<?php

declare(strict_types=1);

namespace App\Enum\Dictionary;

/**
 * Class DictionaryNameEnum
 * @package App\Enum\Dictionary
 */
enum DictionaryNameEnum: string
{
    case RODZAJE_ADAPTACJI = 'Rodzaje adaptacji';
    case TYPY_WNIOSKOW = 'Typy wniosków';
    case SPRZET = 'Sprzęt';

    case PROSBA = 'Prośba';

    case KIERUNEK_STUDIOW = 'Kierunek studiów';

    case ROK_STUDIOW = 'Rok studiów';

    case WYDZIAL = 'Wydział';

    case PRZYZNANIE_KIEDY = 'Przyznanie wsparcia tłumacza języka migowego';

    case TRYB_STUDIOW = 'Tryb studiów';

    case SPECJALISCI = 'Specjaliści';

    case KARTA_ADAPTACJI = 'Karta adaptacji';

    case RODZAJ_KONSULTACJI = 'Rodzaj konsultacji';

    case SEMESTR_STUDIOW = 'Semestr studiów';

    case STOPIEN_NIEPELNOSPRAWNOSCI = 'Stopień niepełnosprawności';

    case RODZAJ_NIEPELNOSPRAWNOSCI = 'Rodzaj niepełnosprawności';

    case PREFERENCJE_ASYSTENTA = 'Preferencje asystenta';

    case PREFERENCJE_TLUMACZA_MIGOWEGO = 'Preferencje tłumacza języka migowego';

    case JEZYKI = 'Języki';

}
