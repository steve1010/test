<?php

// Game state is an enum represeting the different states in the game
class GameState
{
    const GAME_NOT_OVER = 0;
    const BLACK_JACK = 1;
    const TOO_HIGH_PLAYER = 2;
    const TOO_HIGH_DEALER = 3;
    const WIN_DEALER = 4;
    const WIN_PLAYER = 5;
}

// Represents the suit of the card (spades, hearts, clubs, diamond)
class Suit
{
    const SPADES = 0;
    const HEARTS = 1;
    const CLUBS = 2;
    const DIAMONDS = 3;

    public static function getSuits()
    {
        return range(0, 3);
    }


    public $suit;

    public function __construct($suit)
    {
        $this->suit = $suit;
    }

    public function __toString()
    {
        switch ($this->suit) {
            case Suit::SPADES:
                $string = 'spades';
                break;
            case Suit::HEARTS:
                $string = 'hearts';
                break;
            case Suit::CLUBS:
                $string = 'clubs';
                break;
            case Suit::DIAMONDS:
                $string = 'diamonds';
                break;
            default:
                $string = 'none';
                break;
        }

        return $string;
    }
}

// Represents the rank of the card (from 2 to ace)
class Rank
{
    const ACE = 1;
    const KING = 13;
    const QUEEN = 12;
    const JACK = 11;

    public static function getRanks()
    {
        return range(1, 13);
    }


    public $rank;

    public function __construct($rank)
    {
        $this->rank = $rank;
    }

    public function __toString()
    {
        $string = (string) $this->rank;

        switch ($this->rank) {
            case Rank::ACE:
                $string = 'ace';
                break;
            case Rank::KING:
                $string = 'king';
                break;
            case Rank::QUEEN:
                $string = 'queen';
                break;
            case Rank::JACK:
                $string = 'jack';
                break;
        }

        return $string;
    }

    public function getValue()
    {
        $value = $this->rank;

        switch ($this->rank) {
            case Rank::ACE:
                $value = 11;
                break;
            case Rank::KING:
            case Rank::QUEEN:
            case Rank::JACK:
                $value = 10;
                break;
            default:
                // if normal number
                break;
        }

        return $value;
    }
}

// A Card consists of the suit and the rank
class Card
{
    public $id;
    public $suit;
    public $rank;

    public function __construct($rank, $suit)
    {
        $this->suit = new Suit($suit);
        $this->rank = new Rank($rank);

        $this->id = $this->rank . '_of_' . $this->suit;
    }

    public function __toString()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->rank->getValue();
    }
}

/*
 * Card deck
 */
$card_deck = [];
$suits = Suit::getSuits();
$ranks = Rank::getRanks();

// Loop through every possible combination of suits and ranks
for($i = 0; $i < sizeof($suits); $i++) {
    for($j = 0; $j < sizeof($ranks); $j++) {
        // Get the current combination
        $suit = $suits[$i];
        $rank = $ranks[$j];

        // Create the card object
        $card = new Card($rank, $suit);

        // Store the card with its id as key
        $card_deck[$card->id] = $card;
    }
}
