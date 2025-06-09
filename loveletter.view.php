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

        /*********** Place your code below:  ************/


//         foreach( array(14,11,15,18,17,19,20,16,13) as $i )
//         {
// //            $expl .= '<b>';
//             $expl .= '<div class="cardexpl_number">';
//                 $expl .=  $this->game->card_types[$i]['value'].' - ';
//             $expl .= '</div>';
//             $expl .= '<div class="cardexpl_descr">';
//                 $expl .= $this->game->card_types[$i]['nametr'];
//       //          $expl .= '</b>';
//                 $expl .= ' ('.$this->game->card_types[$i]['qt'].') : ';
//                 $expl .= $this->game->card_types[$i]['shortdescr'];
//             $expl .= '</div>';
//         }


        /*

        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );

        */

        /*

        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 


        $this->page->begin_block( "loveletter_loveletter", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }

        */

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
  

