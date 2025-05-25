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
  * loveletter.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class loveletter extends Table
{
	function __construct( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array(
            'last' => 10,
            'sycophant' => 11, // player targeted by sycophant
            'jester' => 12,     // player targeted by jester
            'cardinal_1' => 13,
            'cardinal_2' => 14,
         
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );
		
		$this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
	}
	
    protected function getGameName( )
    {
        return "loveletter";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];


        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue( 'last', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        //useful for testing only.
        /*$end_score = $this->getEndScore();
        $player_count = count($players);
        self::notifyAllPlayers("endScore", clienttranslate( 'In a ${player_count} player game, first to ${end_score} wins!' ), array(
            'i18n' => array(),
            'end_score' => $end_score,
            'player_count' => $player_count
        ));*/

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

		$cards = array();

		$players = self::loadPlayersBasicInfos();

		foreach( $this->card_types as $type_id => $type )
		{
            if( count( $players ) > 4 || $type_id <10 ) // Note : cards with type > 10 are for 5+ players only
    		    $cards[] = array( 'type' => $type_id, 'type_arg' => $type_id, 'nbr' => $type['qt'] );
		}

		$this->cards->createCards($cards, 'deck');

        /************ End of the game initialization *****/
    }
    
    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_alive alive, player_protected protection FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        $result['players_nbr'] = count( $result['players'] );
  
        // Gather all information about current game situation (visible by player $current_player_id).
		$result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);
		
		// Note : discarded cards
		$players = self::loadPlayersBasicInfos();
		$result['discard'] = array();
		foreach( $players as $player_id => $player )
		{
		    $result['discard'][ $player_id ] = $this->cards->getCardsInLocation( 'discard'.$player_id, null, 'card_location_arg' );
		}
		$result['last'] = self::getGameStateValue( 'last' );
		
		// Card count
		$result['cardcount'] = $this->cards->countCardsInLocations();
		$result['cardcount']['hand'] = $this->cards->countCardsByLocationArgs( 'hand' );
        if( ! isset( $result['cardcount']['deck'] ) )
            $result['cardcount']['deck'] = 0;

  
        $result['card_types'] = $this->card_types;
        if( count( $result['players'] ) <= 4 )
        {
            // Remove extension cards
            foreach( $result['card_types'] as $i => $card )
            {
                if( $i >= 10 )
                    unset( $result['card_types'][ $i ] );
            }
        }
        
        $result['jester'] = self::getGameStateValue( 'jester' );
        $result['sycophant'] = self::getGameStateValue( 'sycophant' );
  
        return $result;
    }
    
    function updateCardCount()
    {
		$count = $this->cards->countCardsInLocations();
		$count['hand'] = $this->cards->countCardsByLocationArgs( 'hand' );

        if( ! isset( $count['deck'] ) )
            $count['deck'] = 0;

        self::notifyAllPlayers( 'updateCount', '', array( 'count' => $count ) );
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $end_score = $this->getEndScore();
        $max_score = self::getUniqueValueFromDB( "SELECT MAX( player_score ) FROM player" );

        return round( 100 * $max_score / $end_score );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

	function getPlayersToDirection()
    {
        $result = array();
    
        $players = self::loadPlayersBasicInfos();
        $nextPlayer = self::createNextPlayerTable( array_keys( $players ) );

        $current_player = self::getCurrentPlayerId();
        
        $all_directions = [
            2 => ['S', 'N'],
            3 => ['S', 'W', 'E'],
            4 => ['S', 'W', 'N', 'E'],
            5 => ['S', 'W', 'NW', 'N', 'E'],
            6 => ['S', 'W', 'NW', 'N', 'NE', 'E'],
            7 => ['S', 'W', 'NW', 'N', 'NE', 'E', 'SE'],
            8 => ['S', 'SW', 'W', 'NW', 'N', 'NE', 'E', 'SE'],
        ];

        $directions = $all_directions[count($players)] ?? $all_directions[8]; // Default to 8 players
        
        if( ! isset( $nextPlayer[ $current_player ] ) )
        {
            // Spectator mode: take any player for south
            $player_id = $nextPlayer[0];
            $result[ $player_id ] = array_shift( $directions );
        }
        else
        {
            // Normal mode: current player is on south
            $player_id = $current_player;
            $result[ $player_id ] = array_shift( $directions );
        }
        
        while( count( $directions ) > 0 )
        {
            $player_id = $nextPlayer[ $player_id ];
            $result[ $player_id ] = array_shift( $directions );
        }
        return $result;
    }
	

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in loveletter.action.php)
    */
    
    function playCard(int $card_id, array $opponents, int $guess_id)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();

        $card = $this->cards->getCard( $card_id );
        if( $card === null )
            throw new feException( 'This card does not exists' );
        if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
            throw new feException( 'This card is not in your hand' );
            
        // Alright, can play this card
        
        $this->cards->insertCardOnExtremePosition( $card_id, 'discard'.$player_id, true );
        self::setGameStateValue( 'last', $card['type'] );
        
        $bApplyCardEffect = true;
        if( count( $opponents ) == 0 && 
         ($card['type'] == 1 || $card['type'] == 2 || $card['type'] == 3 || $card['type'] == 6 || $card['type'] == 11 || $card['type'] == 12 || $card['type'] == 14 || $card['type'] == 16 || $card['type'] == 19 || $card['type'] == 20 )
        )
        {
            // This player must play a card without an opponent because all opponents are protected or out of the round
            $possible_opponents = self::getObjectListFromDb( "SELECT player_id FROM player WHERE player_id!='$player_id' AND player_protected='0' AND player_alive='1'", true );
            
            if( count( $possible_opponents ) > 0 )
                throw new feException( "You must select an opponent among the ".count( $possible_opponents )." possibles" );
            
            $bApplyCardEffect = false;
        }
        
        if( count( $opponents ) > 0 && self::getGameStateValue( 'sycophant' ) != 0 )
        {
            // Must target the sycophant victim
            $victim = self::getGameStateValue( 'sycophant' );
            if( ! in_array( $victim, $opponents ) )
            {
                if( $victim == $player_id && $card['type'] != 5 && $card['type'] != 17 && $card['type'] != 20 ) // Filter Particular case : the player is NOT forced to target himself if the card cannot target himself
                {
                }
                else
                {
                   throw new feException( sprintf( _('You must target %s because of the Sycophant effect!'), $players[ $victim ]['player_name'] ), true );
                }
            }
        }

        if( self::getGameStateValue( 'sycophant' ) != 0 )
        {
            self::setGameStateValue( 'sycophant', 0 );
            self::notifyAllPlayers( 'sycophant', '', array( 'player' => 0 ) );
        }
        
        // Notify all players about the card played
        $text = clienttranslate( '${player_name} plays ${card_name}' );
        $args = array(
            'i18n' => array( 'card_name' ),
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $this->card_types[ $card['type'] ]['name'],
            'card' => $card
        );
        
        
        if( count( $opponents ) > 0 )
        {
            $opponent_id = reset( $opponents );

            if( count( $opponents ) == 1 )
            {
                $text = clienttranslate( '${player_name} plays ${card_name} against ${player_name2}' );
                $args['player_name2'] = $players[ $opponent_id ]['player_name'];
                $args['opponent_id'] = $opponent_id;
                $args['opponents'] = $opponents;
            }
            else if( count( $opponents ) == 2 )
            {
                $text = clienttranslate( '${player_name} plays ${card_name} against ${player_name2} and ${player_name3}' );
                $args['player_name2'] = $players[ reset( $opponents ) ]['player_name'];
                $args['player_name3'] = $players[ next( $opponents ) ]['player_name'];
                $args['opponent_id'] = reset( $opponents );            
                $args['opponents'] = $opponents;
            }

            
            
            if( $guess_id >= 0 )
            {
                $target_name = $this->card_types[ $guess_id ]['name'];
                if( count( $players ) > 4 )
                {
                    // Specific names
                    if( $guess_id == 14 )
                        $target_name = clienttranslate("Bishop");
                    else if( $guess_id == 7 )
                        $target_name = clienttranslate("Countess or Dowager Queen (7)");
                    else if( $guess_id == 6 )
                        $target_name = clienttranslate("King or Constable (6)");
                    else if( $guess_id == 5 )
                        $target_name = clienttranslate("Prince or Count (5)");
                    else if( $guess_id == 4 )
                        $target_name = clienttranslate("Handmaid or Sycophant (4)");
                    else if( $guess_id == 3 )
                        $target_name = clienttranslate("Baron or Baroness (3)");
                    else if( $guess_id == 2 )
                        $target_name = clienttranslate("Priest or Cardinal (2)");
                    else if( $guess_id == 13 && $card['type'] == 14)
                        $target_name = clienttranslate("Assassin or Jester (0)");
                    else if( $guess_id == 13)
                        $target_name = clienttranslate("Jester");
                }

                $text = clienttranslate( '${player_name} plays ${card_name} against ${player_name2} and ask : are you a ${guess_name}?' );
                $args['guess_name'] = $target_name;
                $args['i18n'][] = 'guess_name';
            }
        }

        if( ! $bApplyCardEffect )
        {
            $text = clienttranslate( '${player_name} plays ${card_name} with no effect (no possible target)' );
            $args['noeffect'] = 1;
        }
        
        $this->updateCardCount();
        self::notifyAllPlayers( "cardPlayedLong", $text, $args );


        if( $card['type'] == 5 || $card['type'] == 6  )
        {
            // If we are playing the Prince or the King, we must ensure that the other card is NOT the countess (because we MUST discard the countess in this case)
            $player_cards = $this->cards->getCardsInLocation( 'hand', $player_id );
            $player_card = reset( $player_cards );
            
            if( $player_card === null )
                throw new feException( "Error: cannot find the other player card" );

            if( $player_card['type'] == 7 )
                throw new feException( self::_("If you have the Countess (7) in your hand with the King (6) or a Prince (5), you MUST discard the Countess."), true );            
        }

        
        if( $bApplyCardEffect )
        {
            // Trigger card effect
            
            
            if( $card['type'] == 1 || $card['type'] == 12 || $card['type'] == 14 )
            {
                // Guard / Bishop : try to guess another player's card

                if( $opponent_id == $player_id )
                    throw new feException( self::_("You must choose an opponent"), true );

                if( self::getUniqueValueFromDB( "SELECT player_protected FROM player WHERE player_id='$opponent_id'" ) == 1 )
                    throw new feException( "This player is protected (handmaid)" );

                $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
                if( count( $opponent_cards ) === 0 )
                    throw new feException( "Error: cannot find opponent card" );
                $opponent_card = reset( $opponent_cards );
                
                if( $guess_id == 1 )
                    throw new feException( "You cannot choose Guard" );
                
                if( $card['type'] == 1 || $card['type'] == 12 )
                    self::incStat( 1, 'guard_played', $player_id );
                else
                    self::incStat( 1, 'bishop_played', $player_id );
                
                if( ( $card['type'] == 1 || $card['type'] == 12 ) && $opponent_card['type'] == 13 )
                {
                    // Assassin ! The Guard is killed.
                    
                    $log =  clienttranslate( 'Guard : ${player_name} is the Assassin! ${player_name2} is out of the round!' );

                    self::notifyAllPlayers( 'cardPlayedResult', clienttranslate( 'Guard : ${player_name} is THE ASSASSIN : ${player_name2} is out of the round!' ), array(
						'i18n' => array( 'guess_name' ),
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'player_name2' => $players[ $player_id ]['player_name'],
                        'guess_name' =>  $target_name,
                        'success' => 2,
                        'card_type' => $card['type'],
                        'player_id' => $opponent_id
                    ) );

                    self::outOfTheRound( $player_id, $opponent_id );      
                    
                    // The assassin player must discard his card and pick a new one

                    // Alright, discard this card
                    
                    $this->cards->insertCardOnExtremePosition( $opponent_card['id'], 'discard'.$opponent_id, true );
                    self::setGameStateValue( 'last', $opponent_card['type'] );
                    
                    // Notify all players about the card played
                    self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} discards its Assassin card' ), array(
                        'player_id' => $opponent_id,
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'card' => $opponent_card,
                        'noeffect'=>1
                    ) );

                    // Pick another card

                    // ... draw 1 card
                    $card = $this->cards->pickCard( 'deck', $opponent_id );

                    if( $card === null )
                    {
                        // No card => draw the card set aside at the beginning of the round
                        $card = $this->cards->pickCard( 'aside', $opponent_id );
                        
                        if( $card === null )
                            throw new feException( "Cannot find card set aside at the beginning of the round" );
                        else
                        {
                            self::notifyAllPlayers( "simpleNote", clienttranslate('Bishop : no more card in the deck : ${player_name} picks the card removed at the beginning of the round.'), array(
                                'player_name' => $players[ $opponent_id ]['player_name']
                            ) ) ;
                        }
                    }

                    self::notifyPlayer( $opponent_id, 'newCard', clienttranslate('You draw a ${card_name}'), array( 
                        'i18n' => array( 'card_name' ),
                        'card' => $card,
                        'card_name' => $this->card_types[ $card['type'] ]['name']
                    ) );
                    
                    
                }
                else if( $this->card_types[ $opponent_card['type'] ]['value'] == $this->card_types[ $guess_id ]['value'] )
                {

                    // Successfully guess !
                    
                    $log =  clienttranslate( 'Guard : ${player_name} is actually a ${guess_name} : out of the round!' );
                    if( $card['type'] == 14 )
                        $log =  clienttranslate( 'Bishop : ${player_name} is actually a ${guess_name}! ${player_name2} gets an affection token.' );
                    
                    self::notifyAllPlayers( 'cardPlayedResult', $log, array(
						'i18n' => array( 'guess_name' ),
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'player_name2' => $players[ $player_id ]['player_name'],
                        'guess_name' =>  $target_name,
                        'success' => 1,
                        'card_type' => $card['type'],
                        'player_id' => $opponent_id
                    ) );

                    
                    if( $card['type'] == 1 || $card['type'] == 12 )
                    {
                        // Guard
                        self::incStat( 1, 'guard_success', $player_id );
                        self::outOfTheRound( $opponent_id, $player_id );                            
                    }
                    else
                    {
                        // Bishop
                        self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='$player_id'" );

                        self::notifyAllPlayers( 'score', '', array(
                            'player_name' => $players[ $player_id ]['player_name'],
                            'player_id' => $player_id,
                            'type' => 'bishop'
                        ) );


                        $this->gamestate->nextState( 'bishopwillchoose' );
                        $this->gamestate->changeActivePlayer( $opponent_id );
                        $this->gamestate->nextState( 'bishoptargeted' );

                        return ;    // Return because we are switching state here

                    }
                }
                else
                {
                    self::notifyAllPlayers( 'cardPlayedResult', clienttranslate( '${player_name} is not a ${guess_name}' ), array(
						'i18n' => array( 'guess_name' ),
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'guess_name' =>  $target_name,
                        'success' => 0,
                        'card_type' => 1,
                        'player_id' => $opponent_id
                    ) );
                }
                
            }
            else if( $card['type'] == 2 || $card['type'] == 19 )
            {
                // Priest / Baroness: look at another player's hand

                if( $card['type'] == 2 )
                    self::incStat( 1, 'priest_played', $player_id );
                else
                    self::incStat( 1, 'baroness_played', $player_id );
                
                foreach( $opponents as $opponent_id )
                {
                    if( $opponent_id == $player_id )
                        throw new feException( self::_("You must choose an opponent"), true );

                    $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
                    if( count( $opponent_cards ) === 0 )
                        throw new feException( "Error: cannot find opponent card" );

                    
                    $opponent_card = reset( $opponent_cards );
                    
                    self::notifyPlayer( $player_id, 'reveal_long', clienttranslate( '${player_name} reveals a ${card_name}'), array(            
                        'i18n' => array( 'card_name' ),
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'player_id' => $opponent_id,
                        'card_type' => $opponent_card['type'],
                        'card_name' => $this->card_types[ $opponent_card['type'] ]['name']
                    ) );
                }
                foreach( $opponents as $opponent_id )
                {
                    self::notifyPlayer( $player_id, 'unreveal', '', array( 'player_id' => $opponent_id ) );
                }
            }
            else if( $card['type'] == 3 || $card['type'] == 11 )
            {
                // Baron : compare cards, the least lose

                if( $opponent_id == $player_id )
                    throw new feException( self::_("You must choose an opponent"), true );

                $player_cards = $this->cards->getCardsInLocation( 'hand', $player_id );
                $player_card = reset( $player_cards );
                
                if( $player_card === null )
                    throw new feException( "Error: cannot find the other player card" );

                if( $card['type'] == 3 )
                    self::incStat( 1, 'baron_played', $player_id );
                else
                    self::incStat( 1, 'dowagerqueen_played', $player_id );

                $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
                if( count( $opponent_cards ) === 0 )
                    throw new feException( "Error: cannot find opponent card" );
                $opponent_card = reset( $opponent_cards );
                
                // Reveal both cards for these 2 players
                self::notifyPlayer( $player_id, 'reveal', clienttranslate( '${player_name} reveals a ${card_name}'), array(            
                    'i18n' => array( 'card_name' ),
                    'player_name' => $players[ $opponent_id ]['player_name'],
                    'player_id' => $opponent_id,
                    'card_type' => $opponent_card['type'],
                    'card_name' => $this->card_types[ $opponent_card['type'] ]['name']
                ) );
                self::notifyPlayer( $player_id, 'unreveal', '', array( 'player_id' => $opponent_id ) );

                self::notifyPlayer( $opponent_id, 'reveal', clienttranslate( '${player_name} reveals a ${card_name}'), array(            
                    'i18n' => array( 'card_name' ),
                    'player_name' => $players[ $player_id ]['player_name'],
                    'player_id' => $player_id,
                    'card_type' => $player_card['type'],
                    'card_name' => $this->card_types[ $player_card['type'] ]['name']
                ) );
                self::notifyPlayer( $opponent_id, 'unreveal', '', array( 'player_id' => $player_id ) );
                
                if( 
                    ( ( $card['type'] == 3 ) && (  $this->card_types[ $opponent_card['type'] ]['value'] > $this->card_types[ $player_card['type'] ]['value'] ) ) 
                    ||
                    ( ( $card['type'] == 11 ) && ( $this->card_types[ $opponent_card['type'] ]['value'] < $this->card_types[ $player_card['type'] ]['value'] ) ) 
                  )
                {
                    // Opponent wins
                    
                    $log = clienttranslate('Baron : ${player_name} has a ${card_name}, lower than ${player_name2}`s card, and is out of this round.');
                    if(  ( $card['type'] == 11 )  )
                        $log = clienttranslate('Dowager Queen : ${player_name} has a ${card_name}, higher than ${player_name2}`s card, and is out of this round.');
                    
                    self::notifyAllPlayers( 'cardPlayedResult', $log , array(
                        'i18n' => array( 'card_name' ),
                        'player_name' => $players[ $player_id ]['player_name'],
                        'card_name' => $this->card_types[ $player_card['type'] ]['name'],
                        'player_name2' => $players[ $opponent_id ]['player_name'],
                        'card_type' => $card['type'],
                        'winner_id' => $opponent_id,
                        'loser_id' => $player_id
                    ) );
                    
                    self::outOfTheRound( $player_id, $opponent_id );            
                }
                else if( 
                    ( ( $card['type'] == 3 ) && ( $this->card_types[ $opponent_card['type'] ]['value'] < $this->card_types[ $player_card['type'] ]['value'] ) ) 
                    ||
                    ( ( $card['type'] == 11 ) && ( $this->card_types[ $opponent_card['type'] ]['value'] > $this->card_types[ $player_card['type'] ]['value'] ) ) 
                  )
                {
                    // Player wins
                    
                    if(  ( $card['type'] == 3 )  )
                    {
                        self::incStat( 1, 'baron_played_success', $player_id );                
                        $log = clienttranslate('Baron : ${player_name} has a ${card_name}, lower than ${player_name2}`s card, and is out of this round.');
                    }
                    else
                    {
                        $log = clienttranslate('Dowager Queen : ${player_name} has a ${card_name}, higher than ${player_name2}`s card, and is out of this round.');
                    }
                    
                    self::notifyAllPlayers( 'cardPlayedResult', $log, array(
                        'i18n' => array( 'card_name' ),
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'card_name' => $this->card_types[ $opponent_card['type'] ]['name'],
                        'player_name2' => $players[ $player_id ]['player_name'],
                        'card_type' => $card['type'],
                        'winner_id' => $player_id,
                        'loser_id' => $opponent_id
                    ) );
                    
                    self::outOfTheRound( $opponent_id, $player_id );            
                }
                else
                {
                    // Tie !

                    if(  ( $card['type'] == 3 )  )
                    {
                        $log = clienttranslate('Baron : ${player_name} and ${player_name2} have the same card, so nothing happens.');
                    }
                    else
                    {
                        $log = clienttranslate('Dowager Queen : ${player_name} and ${player_name2} have the same card, so nothing happens.');
                    }

                    self::notifyAllPlayers( 'cardPlayedResult', $log, array(
                        'player_name' => $players[ $opponent_id ]['player_name'],
                        'player_name2' => $players[ $player_id ]['player_name'],
                        'card_type' => 3,
                        'winner_id' => null,
                        'player1' => $player_id,
                        'player2' => $opponent_id
                    ) );

                }
                 
            }
            else if( $card['type'] == 4 )
            {
                // Handmaid : protection during 1 turn
                self::DbQuery( "UPDATE player SET player_protected='1' WHERE player_id='$player_id'" );
                self::notifyAllPlayers( 'protected', '', array( 'player' => $player_id ) );

                self::incStat( 1, 'handmaid_played', $player_id );
            }
            else if( $card['type'] == 5 )
            {
                // Prince : make opponent discard and take a new card

                $players = self::loadPlayersBasicInfos();

                $cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
                
                if( count( $cards ) == 0 ) 
                    throw new feException( "Cannot find card of player $opponent_id" );
                
                $card = reset( $cards );

                // Alright, discard this card
                
                $this->cards->insertCardOnExtremePosition( $card['id'], 'discard'.$opponent_id, true );
                self::setGameStateValue( 'last', $card['type'] );
                
                // Notify all players about the card played
                self::notifyAllPlayers( "cardPlayed", clienttranslate( 'Prince : ${player_name} discards ${card_name}' ), array(
                    'i18n' => array( 'card_name' ),
                    'player_id' => $opponent_id,
                    'player_name' => $players[ $opponent_id ]['player_name'],
                    'card_name' => $this->card_types[ $card['type'] ]['name'],
                    'card' => $card,
                    'noeffect'=>1
                ) );

                self::incStat( 1, 'prince_played', $player_id );
                
                if( $card['type'] == 8 )
                {
                    // Discard the princess => out of the round!
                    self::notifyAllPlayers( 'simpleNote', clienttranslate('Princess : ${player_name} discards the Princess, and is now out of this round.'), array(
                        'player_name' => $players[ $opponent_id ]['player_name']
                    ) );
                    
                    self::outOfTheRound( $opponent_id, $player_id, true );            

                }
                else
                {            
                    // Pick another card

                    // ... draw 1 card
                    $card = $this->cards->pickCard( 'deck', $opponent_id );

                    if( $card === null )
                    {
                        // No card => draw the card set aside at the beginning of the round
                        $card = $this->cards->pickCard( 'aside', $opponent_id );
                        
                        if( $card === null )
                            throw new feException( "Cannot find card set aside at the beginning of the round" );
                        else
                        {
                            self::notifyAllPlayers( "simpleNote", clienttranslate('Prince : no more card in the deck : ${player_name} picks the card removed at the beginning of the round.'), array(
                                'player_name' => $players[ $opponent_id ]['player_name']
                            ) ) ;
                        }
                    }

                    self::notifyPlayer( $opponent_id, 'newCard', clienttranslate('Prince : you draw a ${card_name}'), array( 
                        'i18n' => array( 'card_name' ),
                        'card' => $card,
                        'card_name' => $this->card_types[ $card['type'] ]['name']
                    ) );
                }
            }
            else if( $card['type'] == 6 || $card['type'] == 20 )
            {
                // King : exchange hands
                
                if( $card['type'] == 6 )
                {                
                    if( $opponent_id == $player_id )
                        throw new feException( self::_("You must choose an opponent"), true );

                    self::incStat( 1, 'king_played', $player_id );
                    
                    $opponent_1 = $player_id;
                    $opponent_2 = $opponent_id;
                }
                else
                {
                    self::incStat( 1, 'cardinal_played', $player_id );

                    $opponent_1 = reset( $opponents );
                    $opponent_2 = next( $opponents );

                }                

                $player_cards = $this->cards->getCardsInLocation( 'hand', $opponent_1 );
                $player_card = reset( $player_cards );
                
                if( $player_card === null )
                    throw new feException( "Error: cannot find the other player card" );

                $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_2 );
                if( count( $opponent_cards ) === 0 )
                    throw new feException( "Error: cannot find opponent card" );
                $opponent_card = reset( $opponent_cards );

                $log = clienttranslate('King : ${player_name} and ${player_name2} exchange their hand.');
                
                if(  $card['type'] == 20 )
                    $log = clienttranslate('Cardinal : ${player_name} and ${player_name2} exchange their hand.');
                

                self::notifyAllPlayers( 'cardexchange', $log, array(
                    'player_name' => $players[ $opponent_2 ]['player_name'],
                    'player_name2' => $players[ $opponent_1 ]['player_name'],
                    'player_1' => $opponent_1,
                    'player_2' => $opponent_2
                ) );
                
                // Exchange hands
                $this->cards->moveCard( $player_card['id'], 'hand', $opponent_2 );
                $this->cards->moveCard( $opponent_card['id'], 'hand', $opponent_1 );
                
                
                self::notifyPlayer( $opponent_2, 'newCard', '', array( 'card' => $player_card, 'from' => $opponent_1, 'remove' => $opponent_card ) );
                self::notifyPlayer( $opponent_1, 'newCard', '', array( 'card' => $opponent_card, 'from' => $opponent_2, 'remove' => $player_card ) );
                
                if(  $card['type'] == 20 )
                {
                    self::setGameStateValue( 'cardinal_1', $opponent_1 );
                    self::setGameStateValue( 'cardinal_2', $opponent_2 );
                    $this->gamestate->nextState( 'cardinalchoice' );
                    return ;
                }
            }        
            else if( $card['type'] == 7 )
            {
                self::incStat( 1, 'countess_played', $player_id );
            }
            else if( $card['type'] == 13 )
            {
                self::incStat( 1, 'assassin_played', $player_id );
            }
            else if( $card['type'] == 15 )
            {
                self::incStat( 1, 'constable_played', $player_id );
            }
            else if( $card['type'] == 18 )
            {
                self::incStat( 1, 'count_played', $player_id );
            }
            else if( $card['type'] == 8 )
            {
                // (suicide)

                self::notifyAllPlayers( 'simpleNote', clienttranslate('Princess : ${player_name} plays the Princess, and is now out of this round.'), array(
                    'player_name' => $players[ $player_id ]['player_name']
                ) );

                self::outOfTheRound( $player_id, $player_id );            


//                throw new feException( self::_("You cannot discard the Princess (otherwise you are out of the round)"), true );
            }
            else if( $card['type'] == 16 )
            {
                // Jester : give a Jester token (yellow heart)

                if( $opponent_id == $player_id )
                    throw new feException( self::_("You must choose an opponent"), true );

                self::incStat( 1, 'jester_played', $player_id );

                self::notifyAllPlayers( 'jester', clienttranslate('Jester : if ${player_name} wins this round, ${player_name2} will score one point too.'), array(
                    'player' => $opponent_id,
                    'player_name' => $players[ $opponent_id ]['player_name'],
                    'player_name2' => $players[ $player_id ]['player_name']
                ) );


                self::setGameStateValue( 'jester', $opponent_id );
            }
            else if( $card['type'] == 17 )
            {
                // Sycophant : give a Sycophan marker

                self::incStat( 1, 'sycophant_played', $player_id );

                self::notifyAllPlayers( 'sycophant', clienttranslate('Sycophant : the card played by next player should target ${player_name}.'), array(
                    'player' => $opponent_id,
                    'player_name' => $players[ $opponent_id ]['player_name']
                ) );


                self::setGameStateValue( 'sycophant', $opponent_id );
            }

        }
                        
        $this->gamestate->nextState( 'playCard' );
        
    }

    function jesterOwnerScore()
    {
        $sql = "SELECT card_location FROM `card` WHERE card_location LIKE 'discard%' AND card_type='16'";
        $jester_location = self::getUniqueValueFromDB( $sql );
    
        $jester_player = substr( $jester_location, 7 );

        self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='$jester_player'" );
        
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'score', clienttranslate('Jester : ${player_name} correctly guess who would win this round and score 1 point.'), array(
            'player_name' => $players[ $jester_player ]['player_name'],
            'player_id' => $jester_player,
            'type' => 'jester'
        ) );
    }
    
    function argsCardinalChoice()
    {
        return array(
            'choice' => array( self::getGameStateValue('cardinal_1'), self::getGameStateValue( 'cardinal_2' ) )
            );
    }

    function cardinalchoice( $opponent_id )
    {
        $player_id = self::getActivePlayerId();
        
        self::checkAction( 'cardinalchoice' );
        
        $players = self::loadPlayersBasicInfos();
        if( $opponent_id != self::getGameStateValue( 'cardinal_1' ) && $opponent_id != self::getGameStateValue( 'cardinal_2' ) )
        {
            throw new feException( sprintf( self::_('You must choose %s or %s'), $players[ $player_id ]['player_name'], $players[ $opponent_id ]['player_name']  ), true );
        }

        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        if( count( $opponent_cards ) === 0 )
            throw new feException( "Error: cannot find opponent card" );

        
        $opponent_card = reset( $opponent_cards );
        
        self::notifyAllPlayers( 'cardinalReveal', clienttranslate( 'Cardinal : ${player_name2} reveals his card to ${player_name}'), array(
            'player_id' => $player_id,
            'opponent_id' => $opponent_id,
            'player_name' => $players[$player_id]['player_name'],
            'player_name2' => $players[$opponent_id]['player_name']
        ) );
        
        self::notifyPlayer( $player_id, 'reveal_long', clienttranslate( '${player_name} reveals a ${card_name}'), array(            
            'i18n' => array( 'card_name' ),
            'player_name' => $players[ $opponent_id ]['player_name'],
            'player_id' => $opponent_id,
            'card_type' => $opponent_card['type'],
            'card_name' => $this->card_types[ $opponent_card['type'] ]['name']
        ) );

        self::notifyPlayer( $player_id, 'unreveal', '', array( 'player_id' => $opponent_id ) );
        
        $this->gamestate->nextState( 'cardinalchoice');
    }
    
    function bishopChoice( $bDiscard )
    {
        self::checkAction( 'bishopdiscard' );
        
        $opponent_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        
        if( $bDiscard )
        {
            // Discard + pick another one
            $cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
            
            if( count( $cards ) == 0 ) 
                throw new feException( "Cannot find card of player $opponent_id" );
            
            $card = reset( $cards );

            // Alright, discard this card
            
            $this->cards->insertCardOnExtremePosition( $card['id'], 'discard'.$opponent_id, true );
            self::setGameStateValue( 'last', $card['type'] );
            
            // Notify all players about the card played
            self::notifyAllPlayers( "cardPlayed", clienttranslate( 'Bishop : ${player_name} discards ${card_name}' ), array(
                'i18n' => array( 'card_name' ),
                'player_id' => $opponent_id,
                'player_name' => $players[ $opponent_id ]['player_name'],
                'card_name' => $this->card_types[ $card['type'] ]['name'],
                'card' => $card,
                'noeffect'=>1
            ) );

            if( $card['type'] == 8 )
            {
                // Discard the princess => out of the round!
                self::notifyAllPlayers( 'simpleNote', clienttranslate('Princess : ${player_name} discards the Princess, and is now out of this round.'), array(
                    'player_name' => $players[ $opponent_id ]['player_name']
                ) );
                
                self::outOfTheRound( $opponent_id, null, true );            

            }
            else
            {            
                // Pick another card

                // ... draw 1 card
                $card = $this->cards->pickCard( 'deck', $opponent_id );

                if( $card === null )
                {
                    // No card => draw the card set aside at the beginning of the round
                    $card = $this->cards->pickCard( 'aside', $opponent_id );
                    
                    if( $card === null )
                        throw new feException( "Cannot find card set aside at the beginning of the round" );
                    else
                    {
                        self::notifyAllPlayers( "simpleNote", clienttranslate('Bishop : no more card in the deck : ${player_name} picks the card removed at the beginning of the round.'), array(
                            'player_name' => $players[ $opponent_id ]['player_name']
                        ) ) ;
                    }
                }

                self::notifyPlayer( $opponent_id, 'newCard', clienttranslate('Bishop : you draw a ${card_name}'), array( 
                    'i18n' => array( 'card_name' ),
                    'card' => $card,
                    'card_name' => $this->card_types[ $card['type'] ]['name']
                ) );
            }


        }
        else
        {
            // Notify all players about the card played
            self::notifyAllPlayers( "bishopGuessKeptCard", clienttranslate( 'Bishop : ${player_name} chooses to keep his card!' ),
            array('player_name' => $players[ $opponent_id ]['player_name']));
        }
        
        // Give the hand to the player who discarded the bishop
        $sql = "SELECT card_location FROM `card` WHERE card_location LIKE 'discard%' AND card_type='14'";
        $bishop_location = self::getUniqueValueFromDB( $sql );
        $bishop_player = substr( $bishop_location, 7 );

        $this->gamestate->nextState( 'bishopdiscard' );
        $this->gamestate->changeActivePlayer( $bishop_player );
        $this->gamestate->nextState( 'nextPlayer' );
    }
    
    function outOfTheRound( $player_id, $killer_id, $bCardAlreadyDiscarded=false )
    {
    
        // If has a Constable in discard pile, gain an affection token
        $players = self::loadPlayersBasicInfos();

        $sql = "SELECT card_id FROM `card` WHERE card_location = 'discard$player_id' AND card_type='15'";
        $constable = self::getUniqueValueFromDB( $sql );
        
        if( $constable !== null )
        {
            self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='$player_id'" );
            
            self::notifyAllPlayers( 'score', clienttranslate('Constable : ${player_name} has been kicked out this round and score 1 point.'), array(
                'player_name' => $players[ $player_id ]['player_name'],
                'player_id' => $player_id,
                'type' => 'constable'
            ) );
        
        }
        
        // Now, kill the player    
    
        self::DbQuery( "UPDATE player SET player_alive='0' WHERE player_id='$player_id'" );
        self::notifyAllPlayers( 'outOfTheRound', '', array(
            'player_id' => $player_id
        ) );
        
        self::incStat( 1, 'killed', $player_id );
        if( $killer_id !== null )
            self::incStat( 1, 'kills', $killer_id );
        

        if( $bCardAlreadyDiscarded == false )
        {
            $cards = $this->cards->getCardsInLocation( 'hand', $player_id );
            
            if( count( $cards ) == 0 ) 
                throw new feException( "Cannot find card of player $player_id" );
            
            $card = reset( $cards );

            // Alright, discard this card
            
            $this->cards->insertCardOnExtremePosition( $card['id'], 'discard'.$player_id, true );
            self::setGameStateValue( 'last', $card['type'] );

            $this->updateCardCount();
            
            // Notify all players about the card played
            self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} (out of the round) discards ${card_name}' ), array(
                'i18n' => array( 'card_name' ),
                'player_id' => $player_id,
                'player_name' => $players[ $player_id ]['player_name'],
                'card_name' => $this->card_types[ $card['type'] ]['name'],
                'card' => $card,
                'noeffect'=>1
            ) );


        }



    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */
    
    function getEndScore()
    {
        $players = self::loadPlayersBasicInfos();

        $player_to_end_score = [
        2 => 6,
        3 => 5,
        4 => 4,
        5 => 3,
        6 => 3
        ];

        $end_score = $player_to_end_score[count($players)];

        return $end_score;
    }
    
    
    function stNewRound()
    {
        $max_score = self::getUniqueValueFromDB( "SELECT MAX( player_score ) FROM player" );
        $players = self::loadPlayersBasicInfos();
        
        $end_score = $this->getEndScore();
        
        if( $max_score >= $end_score )
        {
            self::notifyAllPlayers( 'simpleNote', clienttranslate("This is the end!"), array() );
        
            $this->gamestate->nextState( 'endGame' );           
            return ; 
        }
        
        self::incStat( 1, 'round_number' );
        
        self::notifyAllPlayers( 'newRound', '', array() );
    
        self::setGameStateValue( 'last', 0 );
        
        self::setGameStateValue( 'jester', 0 );
        self::setGameStateValue( 'sycophant', 0 );
    
        // Reform deck
        $this->cards->moveAllCardsInLocation( null, 'deck' );
    
        // Shuffle deck
        $this->cards->shuffle( 'deck' );
        
        // 1 card aside ...
        $this->cards->pickCardForLocation( 'deck', 'aside' );
    
        // Draw one card for each player
        
        foreach( $players as $player_id => $player )
        {
            $card = $this->cards->pickCard( 'deck', $player_id );    
            self::notifyPlayer( $player_id, 'newCard', clienttranslate('A new round begins: you draw a ${card_name}'), array(
                'i18n' => array('card_name'),
                'card' => $card,
                'card_name' => $this->card_types[$card['type']]['name'])
            );
        }

        // +1 card for active player
        $card = $this->cards->pickCard( 'deck', self::getActivePlayerId() );    
        self::notifyPlayer( self::getActivePlayerId(), 'newCard', clienttranslate('At the start of your turn, you draw a ${card_name}'), array(
            'i18n' => array('card_name'),
            'card' => $card,
            'card_name' => $this->card_types[$card['type']]['name'])
        );

        self::DbQuery( "UPDATE player SET player_alive='1', player_protected='0' " );

        $this->updateCardCount();
        
        $this->gamestate->nextState( 'newRound' );
    }
    
    function stNextPlayer()
    {
        $players = self::loadPlayersBasicInfos();
        $player_to_alive = self::getCollectionFromDb( "SELECT player_id, player_alive FROM player WHERE 1", true );
        $alive_count = 0;
        foreach( $player_to_alive as $player_id => $alive )
        {
            if( $alive == 1 )
                $alive_count ++;
        }

        if( $alive_count == 1 )
        {
            // Round winner !

            foreach( $player_to_alive as $player_id => $alive )
            {
                if( $alive == 1 )
                    $winner_id = $player_id;
            }

            
         
            $handcards = $this->cards->getCardsInLocation( 'hand' );
            self::notifyAllPlayers( "endOfRoundExplanation", clienttranslate( 'There is only one player remaining : this is the end of the round' ), array(
                'hands' => $handcards
            ) );


            self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='$winner_id'" );
            $this->gamestate->changeActivePlayer( $winner_id );
			self::giveExtraTime( $winner_id );
            
            self::notifyAllPlayers( 'score', clienttranslate('${player_name} is the only player remaining and wins the round'), array(
                'player_name' => $players[ $winner_id ]['player_name'],
                'player_id' => $winner_id,
                'type' => 'remaining'
            ) );

			if( $winner_id == self::getGameStateValue( 'jester' ) )
			    self::jesterOwnerScore();

            
            self::incStat( 1, 'round_victory_by_latest', $winner_id );
            

            $this->gamestate->nextState( 'endRound' );
        }
        else
        {
            // Check if some player(s) reach 4 points thanks to Constable or Bishop.
            // In this case, this is the immediate end of the game!

            $end_score = $this->getEndScore();

            $max_score = self::getUniqueValueFromDB( "SELECT COUNT( player_id ) FROM player WHERE player_score>=$end_score" );

            if( $max_score > 0 )
            {
                self::notifyAllPlayers( 'simpleNote', clienttranslate("A player has enough points to win the game : Immediate Victory!"), array() );
                $this->gamestate->nextState( 'endGame' );           
            
                return ;
            }        

        
            // Go to next (alive) player

            $next_player = self::getNextPlayerTable();
            $current_player = self::getActivePlayerId();

            while( true )
            {
                $current_player = $next_player[ $current_player ];
                if( $player_to_alive[ $current_player ] == 1 )
                {
                    $this->gamestate->changeActivePlayer( $current_player );
                    
                    // ... draw 1 card
                    $card = $this->cards->pickCard( 'deck', $current_player );
                    
                    
                    if( $card === null )
                    {
                        // No more card in deck! End of round!


                        // Winner is the player alive with the highest card!
                        $handcards = $this->cards->getCardsInLocation( 'hand' );
                        self::notifyAllPlayers( "endOfRoundExplanation", clienttranslate( 'No more cards in the deck : this is the end of the round' ), array( 'hands' => $handcards ) );
                        $player_to_value = array();
                        $player_to_cardtype = array();
                        $player_to_counts = array();

                        $bishop_player = null;
                        $princess_player = null;


                        foreach( $handcards as $handcard )
                        {
                            $player_to_value[ $handcard['location_arg'] ] = $this->card_types[ $handcard['type'] ]['value'];
                            $player_to_cardtype[ $handcard['location_arg'] ] = $handcard['type'];
                            $player_to_counts[ $handcard['location_arg'] ] = 0;
                                                        
                            if( $handcard['type'] == 14 )
                            {
                                $bishop_player = $handcard['location_arg'];
                            }
                            if( $handcard['type'] == 8 )
                            {
                                $princess_player = $handcard['location_arg'];
                            }
                        }


                        if( $bishop_player !== null && $princess_player !== null )
                        {
                            // In ANY case, bishop is beaten by the princess, so remove the bishop from possible winners
                            unset( $player_to_value[ $bishop_player ] );
                            
                            self::notifyAllPlayers( 'simpleNote', 
                            clienttranslate('At the end of a round, despite his impressive 9, the Princess still beats the Bishop when comparing the values of cards in players’ hands.'),
                            array()
                            );
                        }

                        // Get number of counts discarded, and add +1 for each
                        $sql = "SELECT card_location FROM `card` WHERE card_location LIKE 'discard%' AND card_type='18'";
                        $counts_locations = self::getObjectListFromDb( $sql, true );
                        foreach( $counts_locations as $count_location )
                        {
                            // discard<X>
                            $count_player = substr( $count_location, 7 );
                            if( isset( $player_to_value[ $count_player ] ) )
                            {
                                $player_to_value[ $count_player ]++;
                                $player_to_counts[ $count_player ] ++;
                                
                                self::notifyAllPlayers( 'simpleNote', clienttranslate('${player_name} discarded a Count and get +1 to its last card value.'), array( 'player_name' =>  $players[ $count_player ]['player_name'] ) );
                            }
                        }
                        
                        $winner_id = getKeyWithMaximum( $player_to_value );

                        if( $winner_id === null )
                        {
                            // Some players have the same card
                            $possible_winners = getKeysWithMaximum( $player_to_value );
                            
                            // Winner = the one who discards the highest card values
                            $player_to_discard_total = array();

		                    foreach( $possible_winners as $player_id )
		                    {
		                        $player_to_discard_total[ $player_id ] = 0;
		                        $discarded = $this->cards->getCardsInLocation( 'discard'.$player_id, null, 'card_location_arg' );
		                        foreach( $discarded as $card_discarded )
		                        {
    		                        $player_to_discard_total[ $player_id ] += $this->card_types[ $card_discarded['type'] ]['value'];
		                        }
		                        
		                    }

		                    
		                    self::notifyAllPlayers( 'simpleNote', clienttranslate('There are several players with the highest card : the winner is the one who discarded the highest total of cards among them.'), array() );

                            $winner_id = getKeyWithMaximum( $player_to_discard_total );
                            
                            if( $winner_id === null )
                            {
                                // Very particular case : some player are STILL tie
                                // => no winner for this round
                                self::notifyAllPlayers( 'simpleNote', clienttranslate('IN-CRE-DI-BLE : several players discarded the SAME highest total. There is no winner for this round.'), array() );     
                            }
                            else
                            {                            
                                self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='$winner_id'" );
                                $this->gamestate->changeActivePlayer( $winner_id );
                                
                                self::notifyAllPlayers( 'score', clienttranslate('${player_name} discarded the highest total (${total}) and wins the round'), array(
                                    'player_name' => $players[ $winner_id ]['player_name'],
                                    'player_id' => $winner_id,
                                    'total' => $player_to_discard_total[ $winner_id ],
                                    'type' => 'highestdiscarded'
                                ) );

                                self::incStat( 1, 'round_victory_by_highest', $winner_id );

                            }
                            
                        }
                        else
                        {
                            // There is a single winner !
                            
                            self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='$winner_id'" );
                            $this->gamestate->changeActivePlayer( $winner_id );
                            
                            self::notifyAllPlayers( 'score', clienttranslate('${player_name} has the highest card (${card_type} - ${card_name}) and wins the round'), array(
								'i18n' => array( 'card_name' ),
                                'player_name' => $players[ $winner_id ]['player_name'],
                                'player_id' => $winner_id,
                                'card_type' =>  $this->card_types[ $player_to_cardtype[ $winner_id ] ]['value'],
                                'card_name' => $this->card_types[$player_to_cardtype[ $winner_id ] ]['name'],
                                'type' => 'highest'
                            ) );

                            self::incStat( 1, 'round_victory_by_highest', $winner_id );
                            
                        }

            			if ($winner_id) {
                            self::giveExtraTime( $winner_id );
                            
                            if( $winner_id == self::getGameStateValue( 'jester' ) )
                                self::jesterOwnerScore();
                        }
                        else {
                            self::giveExtraTime( $current_player );
                        }

                        self::notifyAllPlayers( 'endOfRoundPause', '', array() );
                        
                        $this->gamestate->nextState( 'endRound' );

                        return ;
                    }
                    else
                    {
            			self::giveExtraTime( $current_player );

                        self::DbQuery( "UPDATE player SET player_protected='0' WHERE player_id='$current_player'" );
                        self::notifyPlayer( $current_player, 'newCard', clienttranslate('At the start of your turn, you draw a ${card_name}'), array(
                            'i18n' => array('card_name'),
                            'card' => $card,
                            'card_name' => $this->card_types[$card['type']]['name'])
                        );
                        $this->gamestate->nextState( 'nextPlayer' );
                        $this->updateCardCount();
                        return ;
                    }
                }
            }
        
        }
        
    
    }
    
    function s()
    {
        $this->cards->shuffle('deck');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
                $hands = $this->cards->getCardsInLocation( 'hand', $active_player );
                shuffle( $hands );
                $to_play = array_shift( $hands );
                
                if( $to_play['type'] == 8 )
                    $to_play = array_shift( $hands );

                $possible_opponents = self::getObjectListFromDb( "SELECT player_id FROM player WHERE player_id!='$active_player' AND player_protected='0' AND player_alive='1'", true );
        
                if( count( $possible_opponents ) > 0 )
                {
                    shuffle( $possible_opponents );
                    $this->playCard( $to_play['id'], array_slice($possible_opponents, 0, 1), bga_rand( 2,8 ) );
                }

                return;

        }

 
        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
}
