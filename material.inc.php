<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * loveletter implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * loveletter game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/


$this->card_types = array(
    1 => array( 
        "name" => clienttranslate('Guard'),
        "nametr" => self::_('Guard'),
        "description" => clienttranslate('Choose a number other than 1 and choose another player. If that player has the corresponding card, he or she is out of the round.'),
        "shortdescr" => self::_("Guess a player's hand."),
        'qt' => 5,
        'value' => 1
    ),
    2 => array( 
        "name" => clienttranslate('Priest'),
        "nametr" => self::_('Priest'),
        "description" => clienttranslate('Look at another player`s hand.'),
        "shortdescr" => self::_("Look at a hand."),
        'qt' => 2,
        'value' => 2
    ),
    3 => array( 
        "name" => clienttranslate('Baron'),
        "nametr" => self::_('Baron'),
        "description" => clienttranslate('You and another player secretly compare hands. The player with the lower value is out of the round.'),
        "shortdescr" => self::_("Compare hands; lower hand is out."),
        'qt' => 2,
        'value' => 3
    ),
    4 => array( 
        "name" => clienttranslate('Handmaid'),
        "nametr" => self::_('Handmaid'),
        "description" => clienttranslate('Until next turn, ignore all effects from other player`s cards.'),
        "shortdescr" => self::_("Protection until your next turn."),
        'qt' => 2,
        'value' => 4
    ),
    5 => array( 
        "name" => clienttranslate('Prince'),
        "nametr" => self::_('Prince'),
        "description" => clienttranslate('Choose any player (including yourself) to discard his or her hand and draw a new card.'),
        "shortdescr" => self::_("One player discards his or her hand."),
        'qt' => 2,
        'value' => 5
    ),
    6 => array( 
        "name" => clienttranslate('King'),
        "nametr" => self::_('King'),
        "description" => clienttranslate('Trade hands with another player of your choice.'),
        "shortdescr" => self::_("Trade hands."),
        'qt' => 1,
        'value' => 6
    ),
    7 => array( 
        "name" => clienttranslate('Countess'),
        "nametr" => self::_('Countess'),
        "description" => clienttranslate('If you have this card and the King or Prince in your hand, you must discard this card.'),
        "shortdescr" => self::_("Discard if caught with King or Prince."),
        'qt' => 1,
        'value' => 7
    ),
    8 => array( 
        "name" => clienttranslate('Princess'),
        "nametr" => self::_('Princess'),
        "description" => clienttranslate('If you discard this card, you are out of the round.'),
        "shortdescr" => self::_("Lost if discarded."),
        'qt' => 1,
        'value' => 8
    ),
    
    // Premium editions cards (5-8 players)

    11 => array( 
        "name" => clienttranslate('Dowager Queen'),
        "nametr" => self::_('Dowager Queen'),
        "description" => clienttranslate('Choose another player. You secretly compare hands with them. The player with the higher number is out of the round.'),
        "shortdescr" => self::_("Compare hands; higher hand is out."),
        'qt' => 1,
        'value' => 7
    ),
    12 => array( 
        "name" => clienttranslate('Guard'),
        "nametr" => self::_('Guard'),
        "description" => clienttranslate('Choose a number other than 1 and choose another player. If that player has the corresponding card, he or she is out of the round.'),
        "shortdescr" => self::_("Guess a player's hand."),
        'qt' => 3,
        'value' => 1
    ),
    13 => array( 
        "name" => clienttranslate('Assassin'),
        "nametr" => self::_('Assassin'),
        "description" => clienttranslate('If you have this card in your hand when another player chooses you as part of a Guard`s effect, they are knocked out of the round and you are not. Discard this card and draw a new card.'),
        "shortdescr" => self::_("If attacked by Guard attacker is out."),
        'qt' => 1,
        'value' => 0
    ),
    14 => array( 
        "name" => clienttranslate('Bishop'),
        "nametr" => self::_('Bishop'),
        "description" => clienttranslate('Name a number of than 1 and choose another player. If they have that number in their hand, gain an Affection Token. They may discard their hand and draw a new card. The Princess beats the Bishop at the end of the round.'),
        "shortdescr" => self::_("Guess a player's hand to score one point."),
        'qt' => 1,
        'value' => 9
    ),
    15 => array( 
        "name" => clienttranslate('Constable'),
        "nametr" => self::_('Constable'),
        "description" => clienttranslate('If this card is in your discard pile when you are knocked out of the round, gain an Affection Token.'),
        "shortdescr" => self::_("Score one point if knocked out."),
        'qt' => 1,
        'value' => 6
    ),
    16 => array( 
        "name" => clienttranslate('Jester'),
        "nametr" => self::_('Jester'),
        "description" => clienttranslate('Choose another player. Give them a Jester token. If they win this round, you gain an Affection Token.'),
        "shortdescr" => self::_("Guess round winner to score one point."),
        'qt' => 1,
        'value' => 0
    ),
    17 => array( 
        "name" => clienttranslate('Sycophant'),
        "nametr" => self::_('Sycophant'),
        "description" => clienttranslate('Choose any player. If the next card played has an effect that requires one or more players to be chosen, they must be one of them.'),
        "shortdescr" => self::_("Choose target of the next effect."),
        'qt' => 2,
        'value' => 4
    ),
    18 => array( 
        "name" => clienttranslate('Count'),
        "nametr" => self::_('Count'),
        "description" => clienttranslate('If this card is in your discard pile at the end of the round, add 1 to the number of the card in your hand. Resolve ties normally.'),
        "shortdescr" => self::_("+1 to your card at the end of the round."),
        'qt' => 2,
        'value' => 5
    ),
    19 => array( 
        "name" => clienttranslate('Baroness'),
        "nametr" => self::_('Baroness'),
        "description" => clienttranslate('Choose one or two other players. Look at their hands.'),
        "shortdescr" => self::_("Look at 1/2 hands"),
        'qt' => 2,
        'value' => 3
    ),
    20 => array( 
        "name" => clienttranslate('Cardinal'),
        "nametr" => self::_('Cardinal'),
        "description" => clienttranslate('Choose two players. They must trade hands. Look at one of their hands.'),
        "shortdescr" => self::_("Make 2 players exchange hands + look at one."),
        'qt' => 2,
        'value' => 2
    ),
    
);



