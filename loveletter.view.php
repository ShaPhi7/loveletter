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
 * loveletter.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in loveletter_loveletter.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  //require_once( APP_BASE_PATH."modules/php/material.inc.php");
  
  class view_loveletter_loveletter extends game_view
  {
    function getGameName() {
        return "loveletter";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );
        $this->tpl['PLAYERS_NBR'] = 'players_nbr_'.$players_nbr;

        $this->tpl['CARD_CONSTANTS'] = self::raw('
            <script type="text/javascript">
                window.CARD_CONSTANTS = {
                    GUARD: ' . loveletter::GUARD . ',
                    PRIEST: ' . loveletter::PRIEST . ',
                    BARON: ' . loveletter::BARON . ',
                    HANDMAID: ' . loveletter::HANDMAID . ',
                    PRINCE: ' . loveletter::PRINCE . ',
                    CHANCELLOR: ' . loveletter::CHANCELLOR . ',
                    KING: ' . loveletter::KING . ',
                    COUNTESS: ' . loveletter::COUNTESS . ',
                    PRINCESS: ' . loveletter::PRINCESS . ',
                    SPY: ' . loveletter::SPY . '
                };
            </script>
        ');

        /*********** Do not change anything below this line  ************/
  	}
  }
  

