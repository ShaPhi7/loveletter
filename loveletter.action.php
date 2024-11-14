<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Love Letter implementation : © Shaun Phillips <smphillips@alumni.york.ac.uk>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * loveletter.action.php
 *
 * loveletter main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/loveletter/loveletter/myAction.html", ...)
 *
 */
  
  
  class action_loveletter extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "loveletter_loveletter";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 

  	// TODO: defines your action entry points there

	public function playCard()
    {
        self::setAjaxMode();     
        $card_id = self::getArg( "card", AT_posint, true );
        $opponents_raw = self::getArg( "opponent", AT_numberlist, false, '');

        // Removing last ';' if exists
        if( substr( $opponents_raw, -1 ) == ',' )
            $opponents_raw = substr( $opponents_raw, 0, -1 );
        if( $opponents_raw == '' )
            $opponents = array();
        else
            $opponents = explode( ',', $opponents_raw );


        $guess_id = self::getArg( "guess", AT_int, true );
        $this->game->playCard( $card_id,$opponents, $guess_id );
        self::ajaxResponse( );
    }

	public function bishopChoice()
    {
        self::setAjaxMode();     
        $bDiscard = self::getArg( 'choice', AT_bool, true );
        $this->game->bishopChoice( $bDiscard );
        self::ajaxResponse( );
    }

    public function cardinalchoice()
    {
        self::setAjaxMode();     
        $player_id = self::getArg( 'choice', AT_posint, true );
        $this->game->cardinalchoice( $player_id );
        self::ajaxResponse( );

    }


    /*

    Example:

    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }

    */

  }
  

