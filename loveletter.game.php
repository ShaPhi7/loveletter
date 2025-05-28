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
    public const GUARD      = 21;
    public const PRIEST     = 22;
    public const BARON      = 23;
    public const HANDMAID   = 24;
    public const PRINCE     = 25;
    public const CHANCELLOR = 26;
    public const KING       = 27;
    public const COUNTESS   = 28;
    public const PRINCESS   = 29;
    public const SPY        = 30;

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

    function notifyPlayCard($card, $opponent_id) {
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        
        if ($opponent_id)
        {
            self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays ${card_name} against ${player_name2}'),
            array(
                'i18n' => array('card_name'),
                'player_name' => $players[$player_id]['player_name'],
                'player_id' => $player_id,
                'card_type' => $this->card_types[$card['type']],
                'card_name' => $this->card_types[$card['type']]['name'],
                'card' => $card,
                'player_name2' => $players[$opponent_id]['player_name'],
                'opponent_id' => $opponent_id,
            ));
        }
        else
        {
            self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays ${card_name}'),
            array(
                'i18n' => array('card_name'),
                'player_name' => $players[$player_id]['player_name'],
                'player_id' => $player_id,
                'card_type' => $this->card_types[$card['type']],
                'card_name' => $this->card_types[$card['type']]['name'],
                'card' => $card
            ));
        }
    }

    /** 
     * If all other alive players are protected by Handmaid 
     */
    function notifyPlayCardWithNoOpponent($card)
    {
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();

        self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays ${card_name} with no effect (no possible target)'), array(
            'i18n' => array('card_name'),
            'player_name' => $players[$player_id]['player_name'],
            'player_id' => $player_id,
            'card_type' => $this->card_types[$card['type']],
            'card_name' => $this->card_types[$card['type']]['name'],
            'card' => $card,
            'noeffect' => 1
        ));
    }
        
    function playGuard($card, int $opponent_id, int $guess_id)
    {
        $player_id = self::getActivePlayerId();

        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoOpponent($card);
            return;
        }

        self::notifyPlayCard($card, $opponent_id);
        
        $players = self::loadPlayersBasicInfos();
        //TODO VALIDATION EVERYWHERE ELSE
        if ($opponent_id == $player_id) {
            throw new feException(self::_("You must choose an opponent"), true);
        }

        if (self::getUniqueValueFromDB("SELECT player_protected FROM player WHERE player_id='$opponent_id'") == 1) {
            throw new feException("This player is protected (handmaid)");
        }

        $opponent_cards = $this->cards->getCardsInLocation('hand', $opponent_id);
        if (count($opponent_cards) === 0) {
            throw new feException("Error: cannot find opponent card");
        }
        $opponent_card = reset($opponent_cards);

        if ($guess_id == self::GUARD) {
            throw new feException("You cannot choose Guard");
        }

        $guess_name = $this->card_types[ $guess_id ]['name'];

        $args['guess_name'] = $guess_name;

        self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays ${card_name} against ${player_name2} and asks, are you a ${guess_name}?'), array(
            'i18n' => array('card_name','guess_name'),
            'player_name' => $players[$player_id]['player_name'],
            'player_name2' => $players[$opponent_id]['player_name'],
            'guess_name' => $guess_name,
            'card_type' => $this->card_types[$card['type']],
            'card_name' => $this->card_types[$card['type']]['name'],
            'card' => $card,
            'player_id' => $player_id,
            'opponent_id' => $opponent_id,
        ));

        if ($this->card_types[$opponent_card['type']]['value'] == $this->card_types[$guess_id]['value']) {
            // Successfully guessed!
            self::incStat(1, 'guard_success', $player_id);
            $log = clienttranslate('Guard: ${player_name} is actually a ${guess_name} and is out of the round!');
            self::notifyAllPlayers('cardPlayedResult', $log, array(
                'i18n' => array('guess_name'),
                'player_name' => $players[$opponent_id]['player_name'],
                'player_name2' => $players[$player_id]['player_name'],
                'guess_name' => $guess_name,
                'success' => 1,
                'card_type' => $card['type'],
                'player_id' => $opponent_id
            ));
            self::outOfTheRound($opponent_id, $player_id);
        } else {
            self::notifyAllPlayers('cardPlayedResult', clienttranslate('${player_name} is not a ${guess_name}'), array(
                'i18n' => array('guess_name'),
                'player_name' => $players[$opponent_id]['player_name'],
                'guess_name' => $guess_name,
                'success' => 0,
                'card_type' => $card['type'],
                'player_id' => $opponent_id
            ));
        }
    }

    function playPriest($card, int $opponent_id)
    {   
        $player_id = self::getActivePlayerId();
        
        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoOpponent($card);
            return;
        }

        self::notifyPlayCard($card, $opponent_id);
        
        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        $opponent_card = reset($opponent_cards);

        $players = self::loadPlayersBasicInfos();
        self::notifyPlayer($player_id, 'reveal_long', clienttranslate('${player_name} reveals a ${card_name}'), array(
            'i18n' => array('card_name'),
            'player_name' => $players[$opponent_id]['player_name'],
            'player_id' => $opponent_id,
            'card_type' => $opponent_card['type'],
            'card_name' => $this->card_types[$opponent_card['type']]['name']
        ));

        self::notifyPlayer($player_id, 'unreveal', '', array('player_id' => $opponent_id));
    }

    function playBaron($card, int $opponent_id)
    {
        $player_id = self::getActivePlayerId();
        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoOpponent($card);
            return;
        }

        self::notifyPlayCard($card, $opponent_id);

        $player_cards = $this->cards->getCardsInLocation( 'hand', $player_id );
        $player_card = reset( $player_cards );

        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        $opponent_card = reset($opponent_cards);

        // Reveal both cards for these 2 players
        self::notifyPlayer($player_id, 'reveal', clienttranslate('${player_name} reveals a ${card_name}'), array(
            'i18n' => array('card_name'),
            'player_name' => $opponent_id['player_name'],
            'player_id' => $opponent_id,
            'card_type' => $opponent_card['type'],
            'card_name' => $this->card_types[$opponent_card['type']]['name']
        ));
        self::notifyPlayer($player_id, 'unreveal', '', array('player_id' => $opponent_id));

        self::notifyPlayer($opponent_id, 'reveal', clienttranslate('${player_name} reveals a ${card_name}'), array(
            'i18n' => array('card_name'),
            'player_name' => $player_id['player_name'],
            'player_id' => $player_id,
            'card_type' => $player_card['type'],
            'card_name' => $this->card_types[$player_card['type']]['name']
        ));
        self::notifyPlayer($opponent_id, 'unreveal', '', array('player_id' => $player_id));

        $players = self::loadPlayersBasicInfos();

        $winner_id = ($this->card_types[$player_card['type']]['value'] > $this->card_types[$opponent_card['type']]['value']) ? $player_id : $opponent_id;
        $loser_id = ($this->card_types[$player_card['type']]['value'] < $this->card_types[$opponent_card['type']]['value']) ? $player_id : $opponent_id;

        if ($winner_id === $loser_id) {
            // Tie, nothing happens
            $log = clienttranslate('Baron: ${player_name} and ${player_name2} have the same card, so nothing happens.');
            self::notifyAllPlayers('cardPlayedResult', $log, array(
                'player_name' => $players[$player_id]['player_name'],
                'player_name2' => $players[$opponent_id]['player_name'],
                'card_type' => $card['type'],
                'player1' => $player_id,
                'player2' => $opponent_id
            ));
        }
        else {
            // Notify players about the result
            $log = clienttranslate('Baron: ${player_name} has a ${card_name}, lower than ${player_name2}`s card, and is out of this round.');
            self::notifyAllPlayers('cardPlayedResult', $log, array(
                'i18n' => array('card_name'),
                'player_name' => $players[$loser_id]['player_name'],
                'card_name' => $this->card_types[$loser_id === $player_id ? $player_card['type'] : $opponent_card['type']]['name'],
                'player_name2' => $players[$winner_id]['player_name'],
                'card_type' => $card['type'],
                'winner_id' => $winner_id,
                'loser_id' => $loser_id
            ));
            // Remove the loser from the round
            self::outOfTheRound($loser_id, $winner_id);

            if ($winner_id === $player_id) {
                self::incStat(1, 'baron_played_success', $player_id);
            }
        }
    }

    function playHandmaid($card, int $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::notifyPlayCard($card, $opponent_id);

        self::DbQuery("UPDATE player SET player_protected='1' WHERE player_id='$player_id'");
        self::notifyAllPlayers('protected', '', array( 'player' => $player_id));
    }

    function playPrince($card, int $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::notifyPlayCard($card, $opponent_id);

        $cards = $this->cards->getCardsInLocation('hand', $opponent_id);
        $card = reset($cards);

        // Alright, discard this card
        $this->cards->insertCardOnExtremePosition($card['id'], 'discard'.$opponent_id, true);
        self::setGameStateValue('last', $card['type']);
        
        $players = self::loadPlayersBasicInfos();
        // Notify all players about the card played
        self::notifyAllPlayers("cardPlayed", clienttranslate('Prince : ${player_name} discards ${card_name}'), array(
            'i18n' => array('card_name'),
            'player_id' => $opponent_id,
            'player_name' => $players[$opponent_id]['player_name'],
            'card_name' => $this->card_types[$card['type']]['name'],
            'card' => $card,
            'noeffect'=> 1
        ));

        if($card['type'] == self::PRINCESS)
        {
            // Discard the princess => out of the round!
            self::notifyAllPlayers('simpleNote', clienttranslate('Princess : ${player_name} discards the Princess, and is now out of this round.'), array(
                'player_name' => $players[$opponent_id]['player_name']
            ));

            self::outOfTheRound($opponent_id, $player_id, true);
        }
        else
        {            
            $card = $this->cards->pickCard('deck', $opponent_id);

            if($card === null)
            {
                // No card => draw the card set aside at the beginning of the round
                $card = $this->cards->pickCard('aside', $opponent_id);
                self::notifyAllPlayers("simpleNote", clienttranslate('Prince: There are no more cards in the deck, so ${player_name} takes the card removed at the beginning of the round.'),
                array(
                    'player_name' => $players[$opponent_id]['player_name']
                ));
            }

            self::notifyPlayer($opponent_id, 'newCard', clienttranslate('Prince: you draw a ${card_name}'), array(
                'i18n' => array('card_name'),
                'card' => $card,
                'card_name' => $this->card_types[$card['type']]['name']
            ));
        }
    }

    function playChancellor($card, int $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::notifyPlayCard($card, $opponent_id);

        // TODO
    }

    function playKing($card, int $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoOpponent($card);
            return;
        }

        self::notifyPlayCard($card, $opponent_id);

        $player_cards = $this->cards->getCardsInLocation( 'hand', $player_id );
        $player_card = reset($player_cards);

        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        $opponent_card = reset($opponent_cards);
        
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'cardexchange', clienttranslate('King: ${player_name} and ${player_name2} exchange their hand.'), array(
            'player_name' => $players[ $player_id ]['player_name'],
            'player_name2' => $players[ $opponent_id ]['player_name'],
            'player_1' => $player_id,
            'player_2' => $opponent_id
        ));
                
        // Exchange hands
        $this->cards->moveCard( $player_card['id'], 'hand', $opponent_id );
        $this->cards->moveCard( $opponent_card['id'], 'hand', $player_id );
                
        self::notifyPlayer( $opponent_id, 'newCard', '', array( 'card' => $player_card, 'from' => $player_id, 'remove' => $opponent_card ) );
        self::notifyPlayer( $player_id, 'newCard', '', array( 'card' => $opponent_card, 'from' => $opponent_id, 'remove' => $player_card ) );
    }

    function playCountess($card, int $opponent_id)
    {
        self::notifyPlayCard($card, $opponent_id);

        // nothing happens
    }

    function playPrincess($card, int $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::notifyPlayCard($card, $opponent_id);

        self::outOfTheRound( $player_id, $player_id );
    }

    function playSpy($card, int $opponent_id)
    {
        self::notifyPlayCard($card, $opponent_id);

        // nothing happens
    }

    function playCard(int $card_id, array $opponents, int $guess_id)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('playCard'); 
        
        $player_id = self::getActivePlayerId();

        $card = $this->cards->getCard( $card_id );
        if( $card === null )
            throw new feException( 'This card does not exist' );
        if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
            throw new feException( 'This card is not in your hand' );

        //play card
        $this->cards->insertCardOnExtremePosition( $card_id, 'discard'.$player_id, true );
        self::setGameStateValue( 'last', $card['type'] );

        $methodMap = [
            self::GUARD      => 'playGuard',
            self::PRIEST     => 'playPriest',
            self::BARON      => 'playBaron',
            self::HANDMAID   => 'playHandmaid',
            self::PRINCE     => 'playPrince',
            self::CHANCELLOR => 'playChancellor',
            self::KING       => 'playKing',
            self::COUNTESS   => 'playCountess',
            self::PRINCESS   => 'playPrincess',
            self::SPY        => 'playSpy',
        ]; 
    
        $method = $methodMap[$card['type']];
        $this->$method($card, $opponents[0] ?? null, $guess_id);
        
        $this->updateCardCount();

        // the name of the stat is of the form "cardtype_played"
        // e.g. "guard_played", "priest_played", etc.
        self::incStat( 1, strtolower($this->card_types[$card['type']]['name']) . '_played', $player_id );
       
        $this->gamestate->nextState('playCard');
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
        self::incStat( 1, 'round_number' );
        
        self::notifyAllPlayers( 'newRound', '', array() );
    
        self::setGameStateValue( 'last', 0 );
    
        // Reform deck
        $this->cards->moveAllCardsInLocation( null, 'deck' );
    
        // Shuffle deck
        $this->cards->shuffle( 'deck' );
        
        // 1 card aside ...
        $this->cards->pickCardForLocation( 'deck', 'aside' );
    
        // Draw one card for each player
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id)
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
    
    function roundShouldEnd()
    {
        // Check if the round should end
        $alive_count = self::getUniqueValueFromDB( "SELECT COUNT(*) FROM player WHERE player_alive='1'" );
        $deck_count = self::getUniqueValueFromDB( "SELECT COUNT(*) FROM card WHERE card_location='deck'" );

        return ($alive_count <= 1 || $deck_count == 0);
    }

    function stNextPlayer()
    {
        if (self::roundShouldEnd())
        {
            $this->gamestate->nextState('endRound');
        }

        $next_player = self::getNextAlivePlayer();

        $this->gamestate->changeActivePlayer($next_player);
        
        // ... draw 1 card
        $card = $this->cards->pickCard('deck', $next_player);
        $this->updateCardCount();

        self::giveExtraTime($next_player);

        self::DbQuery("UPDATE player SET player_protected='0' WHERE player_id='$next_player'");

        self::notifyPlayer($next_player, 'newCard', clienttranslate('At the start of your turn, you draw a ${card_name}'), array(
            'i18n' => array('card_name'),
            'card' => $card,
            'card_name' => $this->card_types[$card['type']]['name'])
        );

        $this->gamestate->nextState('nextPlayer');
    }

    function getNextAlivePlayer()
    {
        $next_player_table = self::getNextPlayerTable();
        $current_player = self::getActivePlayerId();
        $player_to_alive = self::getCollectionFromDB("SELECT player_id, player_alive FROM player");
        
        // Find the next alive active player
        $found = false;
        $start_player = $current_player;
        do {
            if ($player_to_alive[$current_player] == 1) {
                $found = true;
                break;
            }
            $current_player = $next_player_table[$current_player];
        } while ($current_player != $start_player);

        if (!$found)
        {
            //TODO - PANIC
        }

        return $current_player;
    }

    function gameShouldEnd()
    {
        $max_score = self::getUniqueValueFromDB( "SELECT MAX( player_score ) FROM player" );
        $end_score = $this->getEndScore();
        
        if( $max_score >= $end_score )
        {      
            return true; 
        }
        return false;
    }

    function stEndRound()
    {
        //either last player left in, or any players with the joint highest cards.
        self::rewardRoundWinners();
        self::rewardSpy();

        if (self::gameShouldEnd())
        {
            self::notifyAllPlayers('simpleNote', clienttranslate("This is the end of the game!"), array());
            $this->gamestate->nextState('endGame');
        }
        else
        {
            $this->gamestate->nextState('newRound');
        }
    }

    function rewardSpy()
    {
        $alive_player_ids = self::getCollectionFromDB( "SELECT player_id FROM player WHERE player_alive='1'");
        $sql = "SELECT card_location FROM `card` WHERE card_location LIKE 'discard%' AND card_type='".self::SPY."'";
        $spy_locations = self::getObjectListFromDb( $sql, true );
        if (count($spy_locations) === 1)
        {
            $spy_player_id = substr($spy_locations[0], 7); //the card location looks like 'discard12345', so we need to remove the 'discard' part
            if (isset($alive_player_ids[$spy_player_id]))
            {
                self::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_id='$spy_player_id'");
                
                $players = self::loadPlayersBasicInfos();
                self::notifyAllPlayers('score', clienttranslate('Spy: ${player_name} played the only Spy and gains 1 favor token'), array(
                    'player_name' => $players[$spy_player_id]['player_name'],
                    'player_id' => $spy_player_id,
                    'type' => 'spy'
                ));

                self::incStat( 1, 'tokens_gained_from_spy', $spy_player_id );
            }
            //TODO TEST - does this cover the case where one player played both spies?
        }
    }

    function rewardRoundWinners()
    {
        $alive_player_ids = self::getCollectionFromDB( "SELECT player_id FROM player WHERE player_alive='1'");

        if (count($alive_player_ids) === 1)
        {
            $winner_id = array_key_first($alive_player_ids);
            
            self::rewardLastPlayerStanding($winner_id);
        }
        else
        {
            self::rewardHighestCardWinners($alive_player_ids);
        }
    }

    function rewardLastPlayerStanding($winner_id)
    {
        self::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_id='$winner_id'");

        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'score', clienttranslate('${player_name} is the only player remaining and gains 1 favor token'), array(
            'player_name' => $players[$winner_id]['player_name'],
            'player_id' => $winner_id,
            'type' => 'remaining'
        ) );

        self::incStat( 1, 'round_victory_by_latest', $winner_id );
    }

    function rewardHighestCardWinners($alive_player_ids)
    {
        $winners = self::getAlivePlayersWithHighestCard($alive_player_ids);

        // Award a point to each winner
        foreach ($winners as $winner_id)
        {
            self::DbQuery("UPDATE player SET player_score=player_score+1 WHERE player_id='$winner_id'");
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('score', clienttranslate('${player_name} has the highest card (${card_type} - ${card_name}) and gains 1 favor token'), array(
                'i18n' => array('card_name'),
                'player_name' => $players[$winner_id]['player_name'],
                'player_id' => $winner_id,
                'card_type' =>  $this->cards->getCardsInLocation('hand', $winner_id)[0]['type'],
                'card_name' => $this->cards->getCardsInLocation('hand', $winner_id)[0]['name'],
                'type' => 'highest'
            ));

            self::incStat( 1, 'round_victory_by_highest', $winner_id );
        }
    }

    function getAlivePlayersWithHighestCard($alive_player_ids)
    {
        $alive_player_ids = array_keys($alive_player_ids);
        $highest_value = 0;
        $winners = [];
        foreach ($alive_player_ids as $player_id) {
            $hand = $this->cards->getCardsInLocation('hand', $player_id);
            if (count($hand) > 0) {
                $card = reset($hand);
                $value = $this->card_types[$card['type']]['value'];
                if ($value > $highest_value) {
                    $highest_value = $value;
                    $winners = [$player_id];
                } elseif ($value == $highest_value) {
                    $winners[] = $player_id;
                }
            }
        }

        return $winners;
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
