<?php

namespace App\Database\Entity;

use App\Database\Repository\QuestionnaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: QuestionnaireRepository::class)]
class Questionnaire extends BaseEntity
{

    // ============ 1. Ogólne zadowolenie ============

    /**
     * (1) Ogólne zadowolenie
     *
     * Jak oceniasz swoje ogólne doświadczenie korzystania z aplikacji?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q1 {
        get {
            return $this->q1;
        }
        set {
            $this->q1 = $value;
        }
    }

    /**
     * (1) Ogólne zadowolenie
     *
     * Na ile aplikacja spełnia Twoje oczekiwania?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q2 {
        get {
            return $this->q2;
        }
        set {
            $this->q2 = $value;
        }
    }

// ============ 2. Interfejs i użyteczność ============

    /**
     * (2) Interfejs i użyteczność
     *
     * Czy łatwo było znaleźć informacje lub funkcje, których szukałeś/-aś?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q3 {
        get {
            return $this->q3;
        }
        set {
            $this->q3 = $value;
        }
    }

    /**
     * (2) Interfejs i użyteczność
     *
     * Czy układ elementów na stronie jest intuicyjny?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q4 {
        get {
            return $this->q4;
        }
        set {
            $this->q4 = $value;
        }
    }

    /**
     * (2) Interfejs i użyteczność
     *
     * Czy aplikacja działa zgodnie z Twoimi oczekiwaniami?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q5 {
        get {
            return $this->q5;
        }
        set {
            $this->q5 = $value;
        }
    }

// ============ 3. Funkcjonalności ============

    /**
     * (3) Funkcjonalności
     *
     * Czy są funkcje, których Ci brakuje? Jeśli tak, jakie?
     *
     * Odpowiedź otwarta (tekst). Pole opcjonalne.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $q6 = null {
        get {
            return $this->q6;
        }
        set {
            $this->q6 = $value;
        }
    }

    /**
     * (3) Funkcjonalności
     *
     * Jak oceniasz szybkość działania poszczególnych funkcji?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q7 {
        get {
            return $this->q7;
        }
        set {
            $this->q7 = $value;
        }
    }

// ============ 4. Wydajność i stabilność ============

    /**
     * (4) Wydajność i stabilność
     *
     * Czy wystąpiły jakieś problemy techniczne? Jeśli tak — jakie?
     *
     * Odpowiedź otwarta (tekst). Pole opcjonalne.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $q8 = null {
        get {
            return $this->q8;
        }
        set {
            $this->q8 = $value;
        }
    }

    /**
     * (4) Wydajność i stabilność
     *
     * Jak oceniasz czas ładowania stron?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q9 {
        get {
            return $this->q9;
        }
        set {
            $this->q9 = $value;
        }
    }

// ============ 5. Wrażenia wizualne ============

    /**
     * (5) Wrażenia wizualne
     *
     * Jak oceniasz wygląd i estetykę aplikacji?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q10 {
        get {
            return $this->q10;
        }
        set {
            $this->q10 = $value;
        }
    }

    /**
     * (5) Wrażenia wizualne
     *
     * Czy czytelność tekstów i elementów graficznych jest wystarczająca?
     *
     * Skala/liczba punktów zapisana jako liczba całkowita.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    public int $q11 {
        get {
            return $this->q11;
        }
        set {
            $this->q11 = $value;
        }
    }

// ============ 6. Ogólne pytania otwarte (dodatkowe) ============

    /**
     * (6) Pytania otwarte
     *
     * Co najbardziej podoba Ci się w aplikacji?
     *
     * Odpowiedź otwarta (tekst). Pole opcjonalne.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $q12 = null {
        get {
            return $this->q12;
        }
        set {
            $this->q12 = $value;
        }
    }

    /**
     * (6) Pytania otwarte
     *
     * Co chciał(a)byś poprawić w aplikacji?
     *
     * Odpowiedź otwarta (tekst). Pole opcjonalne.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $q13 = null {
        get {
            return $this->q13;
        }
        set {
            $this->q13 = $value;
        }
    }

    /**
     * (6) Pytania otwarte
     *
     * Czy masz sugestie dotyczące nowych funkcjonalności lub usprawnień?
     *
     * Odpowiedź otwarta (tekst). Pole opcjonalne.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $q14 = null {
        get {
            return $this->q14;
        }
        set {
            $this->q14 = $value;
        }
    }
}
