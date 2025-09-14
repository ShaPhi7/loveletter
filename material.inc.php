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

 //21-30 used here as 1-20 had been used in an old edition of the game and wanted to avoid confusion with the old cards.

$this->card_types = array(
    21 => array( 
        "name" => clienttranslate('Guard'),
        "nametr" => self::_('Guard'),
        "description" => clienttranslate('Choose a number other than 1 and choose another player. If that player has the corresponding card, he or she is out of the round.'),
        "shortdescr" => self::_("Guess a player's hand."),
        'qt' => 6,
        'qt_classic' => 5,
        'value' => 1
    ),
    22 => array( 
        "name" => clienttranslate('Priest'),
        "nametr" => self::_('Priest'),
        "description" => clienttranslate('Look at another player`s hand.'),
        "shortdescr" => self::_("Look at a hand."),
        'qt' => 2,
        'qt_classic' => 2,
        'value' => 2
    ),
    23 => array( 
        "name" => clienttranslate('Baron'),
        "nametr" => self::_('Baron'),
        "description" => clienttranslate('You and another player secretly compare hands. The player with the lower value is out of the round.'),
        "shortdescr" => self::_("Compare hands; lower hand is out."),
        'qt' => 2,
        'qt_classic' => 2,
        'value' => 3
    ),
    24 => array( 
        "name" => clienttranslate('Handmaid'),
        "nametr" => self::_('Handmaid'),
        "description" => clienttranslate('Until next turn, ignore all effects from other player`s cards.'),
        "shortdescr" => self::_("Protection until your next turn."),
        'qt' => 2,
        'qt_classic' => 2,
        'value' => 4
    ),
    25 => array( 
        "name" => clienttranslate('Prince'),
        "nametr" => self::_('Prince'),
        "description" => clienttranslate('Choose any player (including yourself) to discard his or her hand and draw a new card.'),
        "shortdescr" => self::_("One player discards his or her hand."),
        'qt' => 2,
        'qt_classic' => 2,
        'value' => 5
    ),
    26 => array( 
        "name" => clienttranslate('Chancellor'),
        "nametr" => self::_('Chancellor'),
        "description" => clienttranslate('Draw 2 cards. Keep 1 card and put your other 2 on the bottom of the deck in any order.'),
        "shortdescr" => self::_("Take 2 cards, keep 1, discard 2."),
        'qt' => 2,
        'qt_classic' => 0,
        'value' => 6
    ),
    27 => array( 
        "name" => clienttranslate('King'),
        "nametr" => self::_('King'),
        "description" => clienttranslate('Trade hands with another player of your choice.'),
        "shortdescr" => self::_("Trade hands."),
        'qt' => 1,
        'qt_classic' => 1,
        'value' => 7
    ),
    28 => array( 
        "name" => clienttranslate('Countess'),
        "nametr" => self::_('Countess'),
        "description" => clienttranslate('If you have this card and the King or Prince in your hand, you must discard this card.'),
        "shortdescr" => self::_("Discard if caught with King or Prince."),
        'qt' => 1,
        'qt_classic' => 1,
        'value' => 8
    ),
    29 => array( 
        "name" => clienttranslate('Princess'),
        "nametr" => self::_('Princess'),
        "description" => clienttranslate('If you discard this card, you are out of the round.'),
        "shortdescr" => self::_("Lost if discarded."),
        'qt' => 1,
        'qt_classic' => 1,
        'value' => 9
    ),
    30 => array(
        "name" => clienttranslate('Spy'),
        "nametr" => self::_('Spy'),
        "description" => clienttranslate('At the end of the round, if you are the only player in the round who played or discarded a spy, gain 1 favor token.'),
        "shortdescr" => self::_("Gain 1 favor token if you are the only player with a spy."),
        'qt' => 2,
        'qt_classic' => 0,
        'value' => 0
    ), 
);



