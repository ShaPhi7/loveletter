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
 * stats.inc.php
 *
 * loveletter game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice ("Your game configuration" section):
    http://en.studio.boardgamearena.com/admin/studio
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "round_number" => array("id"=> 10,
                    "name" => totranslate("Number of rounds"),
                    "type" => "int" ),

    ),
    
    // Statistics existing for each player
    "player" => array(

        "round_victory_by_highest" => array("id"=> 10,
                    "name" => totranslate("Tokens gained by having the highest card"),
                    "type" => "int" ),
        "round_victory_by_latest" => array("id"=> 11,
                    "name" => totranslate("Tokens gained by being the latest player"),
                    "type" => "int" ),
        "tokens_gained_from_spy" => array("id"=> 12,
                    "name" => totranslate("Tokens gained from Spy"),
                    "type" => "int" ),

        "killed" => array("id"=> 13,
                    "name" => totranslate("Has been knocked out of the round"),
                    "type" => "int" ),
        "kills" => array("id"=> 14,
                    "name" => totranslate("Has knocked out of the round an opponent"),
                    "type" => "int" ),



        "guard_played" => array("id"=> 15,
                    "name" => totranslate("Guards played"),
                    "type" => "int" ),
        "guard_success" => array("id"=> 16,
                    "name" => totranslate("Guards played with success"),
                    "type" => "int" ),
        "priest_played" => array("id"=> 17,
                    "name" => totranslate("Priest played"),
                    "type" => "int" ),
        "baron_played" => array("id"=> 18,
                    "name" => totranslate("Baron played"),
                    "type" => "int" ),
        "baron_played_success" => array("id"=> 19,
                    "name" => totranslate("Baron played with success"),
                    "type" => "int" ),
        "handmaid_played" => array("id"=> 20,
                    "name" => totranslate("Handmaid played"),
                    "type" => "int" ),
        "prince_played" => array("id"=> 21,
                    "name" => totranslate("Prince played"),
                    "type" => "int" ),
        "king_played" => array("id"=> 22,
                    "name" => totranslate("King played"),
                    "type" => "int" ),
        "countess_played" => array("id"=> 23,
                    "name" => totranslate("Countess played"),
                    "type" => "int" ),
        "princess_played" => array("id"=> 24,
                    "name" => totranslate("Princess discarded"),
                    "type" => "int" ),
        "spy_played" => array("id"=> 25,
                    "name" => totranslate("Spy played"),
                    "type" => "int" ),
        "chancellor_played" => array("id"=> 26,
                    "name" => totranslate("Chancellor played"),
                    "type" => "int" )
    )
);
