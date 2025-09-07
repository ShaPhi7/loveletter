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

    public bool $activateChancellorState = false;

	function __construct( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array(
            'last' => 10, //TODO - what does this do?
            
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
        foreach ($players as $player_id => $player)
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('last', 0);

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

		foreach ($this->card_types as $type_id => $type)
		{
    		$cards[] = array('type' => $type_id, 'type_arg' => $type_id, 'nbr' => $type['qt']);
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
        $result['players'] = self::getCollectionFromDb($sql);
        $result['players_nbr'] = count($result['players']);

        // Gather all information about current game situation (visible by player $current_player_id).
		$result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);
		$result['deck'] = $this->cards->getCardsInLocation('deck', null, 'card_location_arg');
		// Note : discarded cards
		$players = self::loadPlayersBasicInfos();
		$result['discard'] = array();
		foreach ($players as $player_id => $player)
		{
		    $result['discard'][$player_id] = $this->cards->getCardsInLocation('discard'.$player_id, null, 'card_location_arg');
		}
		$result['last'] = self::getGameStateValue('last');

		// Card count
		$result['cardcount'] = $this->cards->countCardsInLocations();
		$result['cardcount']['hand'] = $this->cards->countCardsByLocationArgs('hand');
        if (!isset($result['cardcount']['deck']))
            $result['cardcount']['deck'] = 0;

        $result['card_types'] = $this->card_types;
  
        return $result;
    }
    
    function updateCardCount()
    {
		$count = $this->cards->countCardsInLocations();
		$count['hand'] = $this->cards->countCardsByLocationArgs('hand');

        if (!isset($count['deck']))
            $count['deck'] = 0;

        self::notifyAllPlayers('updateCount', '', array('count' => $count));
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
        $max_score = self::getUniqueValueFromDB("SELECT MAX( player_score ) FROM player");

        return round(100 * $max_score / $end_score);
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
        //throw new feException("TEST");
        
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('playCard'); 
        
        $player_id = self::getActivePlayerId();
        $card = $this->cards->getCard($card_id);

        self::validateCard($card);

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
        self::incStat(1, strtolower($this->card_types[$card['type']]['name']) . '_played', $player_id);
       
        if ($this->activateChancellorState) {
            $this->gamestate->nextState('chancellor');
            $this->activateChancellorState = false;
        }
        else {
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function validateCard($card)
    {
        $player_id = self::getActivePlayerId();
        if ($card === null)
        {
            throw new feException( 'This card does not exist' );
        }

        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id)
        {
            throw new feException( 'This card is not in your hand' );
        }    
    }

    function validateOpponent($opponent_id)
    {
        self::validatePlayer($opponent_id);

        $player_id = self::getActivePlayerId();
        if ($opponent_id == $player_id) {
            throw new feException("You cannot play this card against yourself");
        }

        if (self::getUniqueValueFromDB("SELECT player_protected FROM player WHERE player_id='$opponent_id'") == 1) {
            throw new feException("This player is protected by the handmaid and cannot be targeted");
        }

        if (self::getUniqueValueFromDB("SELECT player_alive FROM player WHERE player_id='$opponent_id'") == 0) {
            throw new feException("This player is out of the round and cannot be targeted");
        }

        $opponent_cards = $this->cards->getCardsInLocation('hand', $opponent_id);
        if (count($opponent_cards) === 0) {
            throw new feException("Error: cannot find opponent card");
        }
    }

    function validatePlayer($player_id)
    {
        $players = self::loadPlayersBasicInfos();
        if (!isset($players[$player_id])) {
            throw new feException("This player does not exist");
        }
    }

    function validatePlayerNotHoldingCountess($player_id)
    {
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        foreach ($cards as $card) {
            if ($card['type'] == self::COUNTESS) {
                throw new feException("You cannot play this card while holding the Countess");
            }
        }
    }

    function validatePlayersOtherCardExists($player_id)
    {
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        if (count($cards) === 0) {
            throw new feException("Error: cannot find the player's other card");
        }
    }

    function validateGuard($opponent_id, $guess_id)
    {
        self::validateOpponent($opponent_id);

        if ($guess_id == self::GUARD) {
            throw new feException("You cannot choose Guard");
        }
    }
        
    function playGuard($card, $opponent_id, int $guess_id)
    {
        // self::notifyAllPlayers('simpleNote', clienttranslate('${opponent}, ${card}, ${guess}'), array(
        //                     'opponent' => $opponent_id,
        //                     'card' => $card,
        //                     "guess" => $guess_id
        // ));
        
        $player_id = self::getActivePlayerId();

        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoPossibleTarget($card);
            return;
        }

        self::validateGuard($opponent_id, $guess_id);

        self::notifyCardPlayed($card, $opponent_id, $guess_id);

        $players = self::loadPlayersBasicInfos();

        $opponentCards = $this->cards->getCardsInLocation('hand', $opponent_id);
        $opponent_card = reset($opponentCards);

        $guess_name = $this->card_types[ $guess_id ]['name'];

        $args['guess_name'] = $guess_name;

        if ($this->card_types[$opponent_card['type']]['value'] == $this->card_types[$guess_id]['value']) {
            // Successfully guessed!
            self::incStat(1, 'guard_success', $player_id);
            self::notifyAllPlayers('cardPlayedResult', '', array(
                'i18n' => array('guess_name'),
                'player_name' => $players[$opponent_id]['player_name'],
                'player_name2' => $players[$player_id]['player_name'],
                'guess_name' => $guess_name,
                'success' => 1,
                'card_type' => $card['type'],
                'player_id' => $opponent_id
            ));
            self::outOfTheRound($card, $opponent_id, $player_id);
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

    function validatePriest($opponent_id)
    {
        self::validateOpponent($opponent_id);
    }

    function playPriest($card, $opponent_id)
    {   
        $player_id = self::getActivePlayerId();
        
        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoPossibleTarget($card);
            return;
        }

        self::validatePriest($opponent_id);

        self::notifyPlayCard($card, $opponent_id);
        
        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        $opponent_card = reset($opponent_cards);

        $players = self::loadPlayersBasicInfos();
        self::notifyPlayer($player_id, 'reveal_long', self::getLogTextCardReveal(), array(
            'i18n' => array('card_name'),
            'player_name' => $players[$opponent_id]['player_name'],
            'player_id' => $opponent_id,
            'card_type' => $opponent_card['type'],
            'card_name' => $this->card_types[$opponent_card['type']]['name']
        ));

        //self::notifyPlayer($player_id, 'unreveal', '', array('player_id' => $opponent_id));
    }

    function validateBaron($player_id, $opponent_id)
    {
        self::validatePlayersOtherCardExists($player_id);
        self::validateOpponent($opponent_id);
    }

    function playBaron($card, $opponent_id)
    {
        $player_id = self::getActivePlayerId();
        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoPossibleTarget($card);
            return;
        }

        self::validateBaron($player_id, $opponent_id);

        self::notifyPlayCard($card, $opponent_id);

        $player_cards = $this->cards->getCardsInLocation( 'hand', $player_id );
        $player_card = reset( $player_cards );

        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        $opponent_card = reset($opponent_cards);

        $players = self::loadPlayersBasicInfos();

        // Reveal both cards for these 2 players
        self::notifyPlayer($player_id, 'reveal', self::getLogTextCardReveal(), array(
            'i18n' => array('card_name'),
            'player_name' => $players[$opponent_id]['player_name'],
            'player_id' => $opponent_id,
            'card_type' => $opponent_card['type'],
            'card_name' => $this->card_types[$opponent_card['type']]['name']
        ));

        self::notifyPlayer($opponent_id, 'reveal', self::getLogTextCardReveal(), array(
            'i18n' => array('card_name'),
            'player_name' => $players[$player_id]['player_name'],
            'player_id' => $player_id,
            'card_type' => $player_card['type'],
            'card_name' => $this->card_types[$player_card['type']]['name']
        ));

        $players = self::loadPlayersBasicInfos();

        $winner_id = ($this->card_types[$player_card['type']]['value'] > $this->card_types[$opponent_card['type']]['value']) ? $player_id : $opponent_id;
        $loser_id = ($this->card_types[$player_card['type']]['value'] < $this->card_types[$opponent_card['type']]['value']) ? $player_id : $opponent_id;
 
        if ($winner_id === $loser_id) {
            // Tie, nothing happens
            $log = clienttranslate('Baron: ${player_name} and ${player_name2} have the same card, so nothing happens.');
            self::notifyAllPlayers('simpleNote', $log, array(
                'player_name' => $players[$player_id]['player_name'],
                'player_name2' => $players[$opponent_id]['player_name'],
                'player1' => $player_id,
                'player2' => $opponent_id
            ));
        }
        else {
            self::outOfTheRound($card, $loser_id, $winner_id);

            if ($winner_id === $player_id) {
                self::incStat(1, 'baron_played_success', $player_id);
            }
        }
    }

    function playHandmaid($card, $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::notifyPlayCard($card, $opponent_id);

        self::DbQuery("UPDATE player SET player_protected='1' WHERE player_id='$player_id'");
        self::notifyAllPlayers('protected', '', array( 'player' => $player_id));
    }

    function validatePrince($player_id, $opponent_id)
    {
        self::validatePlayerNotHoldingCountess($player_id);
        self::validatePlayer($opponent_id);
        $cards = $this->cards->getCardsInLocation('aside');
        if (count($cards) === 0) {
            //normally the player takes a card from the deck,
            //if there are no cards in the deck then the player
            //takes the card that was set aside at the beginning of the round
            //if there are no cards set aside,
            //then there are no cards in the deck, so the game should already have finished
            throw new feException("There are no cards set aside, so the round should be over.");
        }
    }

    function playPrince($card, $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::validatePrince($player_id, $opponent_id);

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
            'card_type' => $this->card_types[$card['type']],
            'card_name' => $this->card_types[$card['type']]['name'],
            'card' => $card,
        ));

        if($card['type'] == self::PRINCESS)
        {
            self::outOfTheRound($card, $opponent_id, $player_id);
        }
        else
        {            
            $card = $this->cards->pickCard('deck', $opponent_id);

            if ($card === null)
            {
                // No card => draw the card set aside at the beginning of the round
                $card = $this->cards->pickCard('aside', $opponent_id);
                self::notifyAllPlayers("simpleNote", clienttranslate('Prince: There are no more cards in the deck, so ${player_name} takes the card removed at the beginning of the round.'),
                array(
                    'player_name' => $players[$opponent_id]['player_name']
                ));
            }

            self::notifyPlayer($opponent_id, 'newCardPrivate', clienttranslate('Prince: you draw ${card_name}'), array(
                'i18n' => array('card_name'),
                'card' => $card,
                'card_name' => $this->card_types[$card['type']]['name']
            ));

            //the current player will ignore this notification on client side.
            self::notifyAllPlayers('newCardPublic', '', array(
                'player_id' => $opponent_id,
            ));
        }
    }

    function playChancellor($card, $opponent_id)
    {
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();

        self::notifyPlayCard($card, $opponent_id);

        $deck_count = $this->cards->countCardInLocation('deck');
        switch ($deck_count) {
            case 0:
                self::notifyAllPlayers('simpleNote', clienttranslate('Chancellor: this card has no effect as there are no cards left in the deck'), array(
                    'i18n' => array('card_name'),
                    'player_name' => $players[$player_id]['player_name'],
                    'card_name' => $this->card_types[$card['type']]['name']
                ));
            break;

            case 1:
                $card_1 = $this->cards->pickCard('deck', $player_id);
               
                self::notifyAllPlayers('simpleNote', clienttranslate('Chancellor: ${player_name} only draws one card, as there is only one card left in the deck'), array(
                    'player_name' => $players[$player_id]['player_name'],
                ));
                
                self::notifyPlayer($player_id, 'newCardPrivate', clienttranslate('Chancellor: you draw ${card_name} (only one card left in the deck)'), array(
                    'i18n' => array('card_name'),
                    'card' => $card,
                    'card_name' => $this->card_types[$card_1['type']]['name'],
                    'card_name_2' => ''
                ));
                
                //the current player will ignore this notification on client side.
                self::notifyAllPlayers('newCardPublic', '', array(
                    'player_id' => $player_id,
                ));

                $this->activateChancellorState = true;
            break;

            default:
                $card_1 = $this->cards->pickCard('deck', $player_id);
                $card_2 = $this->cards->pickCard('deck', $player_id);
            
                self::notifyAllPlayers('simpleNote', clienttranslate('Chancellor: ${player_name} draws two cards'), array(
                    'player_name' => $players[$player_id]['player_name'],
                ));

                self::notifyPlayer($player_id, 'chancellor_draw', clienttranslate('Chancellor: you draw ${card_name} and ${card_name_2}'), array(
                'i18n' => array('card_name', 'card_name_2'),
                'card' => $card_1,
                'card_2' => $card_2,
                'card_name' => $this->card_types[$card_1['type']]['name'],
                'card_name_2' => $this->card_types[$card_2['type']]['name']
                ));

                //TODO - other players need to see drawing two cards
                //the current player will ignore this notification on client side.
                // self::notifyAllPlayers('newCardPublic', '', array(
                //     'player_id' => $player_id,
                // ));

                $this->activateChancellorState = true;
            break;
        }
    }

    function validateActionChancellor($keep, $bottom)
    {
        $player_id = self::getActivePlayerId();
        $player_cards = $this->cards->getCardsInLocation('hand', $player_id);
        $valid_card_ids = array_map(function($card) { return $card['id']; }, $player_cards);

        if (!in_array($keep, $valid_card_ids)) {
            throw new feException("You must choose one of the cards in your hand to keep");
        }

        if (!in_array($bottom, $valid_card_ids)) {
            throw new feException("You must choose one of the cards in your hand to place on the bottom of the deck");
        }
    }

    function actionChancellor($keep, $bottom)
    {
        self::validateActionChancellor($keep, $bottom);

        $players = self::loadPlayersBasicInfos();
        $player_id = self::getActivePlayerId();
        $player_cards = $this->cards->getCardsInLocation('hand', $player_id);
        
        foreach ($player_cards as $card) {
        $card_by_id[$card['id']] = $card;
        }

        $keep_card = $card_by_id[$keep];
        $bottom_card = $card_by_id[$bottom];
        $other_card = null;
        foreach ($player_cards as $card) {
        if ($card['id'] != $keep && $card['id'] != $bottom) {
            $other_card = $card;
            break;
            }
        }

        if ($other_card != null) //happens if not enough cards in deck, then player only have draws 1 instead of 2.
        {
            $this->cards->insertCardOnExtremePosition($other_card['id'], 'deck', false);
        }
        $this->cards->insertCardOnExtremePosition($bottom_card['id'], 'deck', false);

        self::notifyAllPlayers('simpleNote', clienttranslate('Chancellor: ${player_name} keeps 1 card, and places the other 2 on the bottom of the deck'), array(
            'player_name' => $players[$player_id]['player_name'],
        ));

        //TODO - add other and bottom card
        self::notifyPlayer($player_id, 'chancellor_bury', clienttranslate('Chancellor: you keep ${card_name}'), array(
            'i18n' => array('card_name'),
            'card' => $keep_card,
            'card_name' => $this->card_types[$keep_card['type']]['name'],
        ));

        $this->gamestate->nextState('nextPlayer');
    }

    function validateKing($player_id, $opponent_id)
    {
        self::validatePlayerNotHoldingCountess($player_id);
        self::validatePlayersOtherCardExists($player_id);
        self::validateOpponent($opponent_id);
    }

    function playKing($card, $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        if (!$opponent_id)
        {
            self::notifyPlayCardWithNoPossibleTarget($card);
            return;
        }

        self::validateKing($player_id, $opponent_id);

        self::notifyPlayCard($card, $opponent_id);

        $player_cards = $this->cards->getCardsInLocation( 'hand', $player_id );
        $player_card = reset($player_cards);

        $opponent_cards = $this->cards->getCardsInLocation( 'hand', $opponent_id );
        $opponent_card = reset($opponent_cards);
                
        // Exchange hands
        $this->cards->moveCard( $player_card['id'], 'hand', $opponent_id );
        $this->cards->moveCard( $opponent_card['id'], 'hand', $player_id );
                
        $players = self::loadPlayersBasicInfos();
        self::notifyAllPlayers( 'cardexchange_opponents', self::getLogTextCardExchange(), array(
            'player_name' => $players[ $player_id ]['player_name'],
            'player_name2' => $players[ $opponent_id ]['player_name'],
            'player_1' => $player_id,
            'player_2' => $opponent_id,
        ));

        self::notifyPlayer( $opponent_id, 'cardexchange', '', array(
            'player_name' => $players[ $player_id ]['player_name'],
            'player_name2' => $players[ $opponent_id ]['player_name'],
            'player_1' => $player_id,
            'player_2' => $opponent_id, 
            'player_1_card' => $player_card,
            'player_2_card' => $opponent_card) );

        self::notifyPlayer( $player_id, 'cardexchange', '', array(
            'player_name' => $players[ $opponent_id ]['player_name'],
            'player_name2' => $players[ $player_id ]['player_name'],
            'player_1' => $opponent_id,
            'player_2' => $player_id,
            'player_1_card' => $opponent_card,
            'player_2_card' => $player_card) );
    }

    function playCountess($card, $opponent_id)
    {
        
        self::notifyPlayCard($card, $opponent_id);

        // nothing happens
    }

    function playPrincess($card, $opponent_id)
    {
        $player_id = self::getActivePlayerId();

        self::notifycardPlayed($card, $opponent_id, null, true);

        self::outOfTheRound($card, $player_id, $player_id);
    }

    function playSpy($card, $opponent_id)
    {
        self::notifyPlayCard($card, $opponent_id);

        // nothing happens
    }

    function outOfTheRound($cardPlayed, $player_id, $killer_id)
    {
        $players = self::loadPlayersBasicInfos();

        self::DbQuery("UPDATE player SET player_alive='0' WHERE player_id='$player_id'");

        self::incStat(1, 'killed', $player_id);
        if ($killer_id !== null)
            self::incStat(1, 'kills', $killer_id);

        $notify_args = array(
            'player_id' => $player_id,
            'player_name' => $players[$player_id]['player_name'],
            'bubble' => $this->getBubbleTextOutOfTheRound($cardPlayed)
        );

        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        if (count($cards) > 0) {
            $cardInHand = reset($cards);

            $this->cards->insertCardOnExtremePosition($cardInHand['id'], 'discard' . $player_id, true);
            self::setGameStateValue('last', $cardInHand['type']);

            $this->updateCardCount();

            $notify_args['card'] = $cardInHand;
            $notify_args['i18n'] = array('card_name');
            $notify_args['card_type'] = $this->card_types[$cardInHand['type']];
            $notify_args['card_name'] = $this->card_types[$cardInHand['type']]['name'];
        }

        self::notifyAllPlayers("outOfTheRound", self::getLogTextOutOfTheRound($cardPlayed), $notify_args);
    }

    function notifyPlayCard($card, $opponent_id) {
        self::notifycardPlayed($card, $opponent_id, null);
    }

    function notifycardPlayed($card, $opponent_id, $guess_id, $silent=false) {
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        
        $bubble_text = self::getBubbleTextCardPlayed($card);

        if ($guess_id)
        {
            self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays a ${card_name} against ${player_name2} and asks, are you a ${guess_name}?'),
            array(
                'i18n' => array('card_name','guess_name'),
                'player_name' => $players[$player_id]['player_name'],
                'player_name2' => $players[$opponent_id]['player_name'],
                'guess_name' => $this->card_types[ $guess_id ]['name'],
                'card_type' => $this->card_types[$card['type']],
                'card_name' => $this->card_types[$card['type']]['name'],
                'card' => $card,
                'player_id' => $player_id,
                'opponent_id' => $opponent_id,
                'bubble' => $bubble_text
            ));
        }
        else if ($opponent_id)
        {
            self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays a ${card_name} against ${player_name2}'),
            array(
                'i18n' => array('card_name'),
                'player_name' => $players[$player_id]['player_name'],
                'player_id' => $player_id,
                'card_type' => $this->card_types[$card['type']],
                'card_name' => $this->card_types[$card['type']]['name'],
                'card' => $card,
                'player_name2' => $players[$opponent_id]['player_name'],
                'opponent_id' => $opponent_id,
                'bubble' => $bubble_text
            ));
        }
        else if ($silent)
        {
            self::notifyAllPlayers('cardPlayed', '',
            array(
                'i18n' => array('card_name'),
                'player_name' => $players[$player_id]['player_name'],
                'player_id' => $player_id,
                'card_type' => $this->card_types[$card['type']],
                'card_name' => $this->card_types[$card['type']]['name'],
                'card' => $card,
                'bubble' => $bubble_text
            ));
        }
        else
        {
            self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays a ${card_name}'),
            array(
                'i18n' => array('card_name'),
                'player_name' => $players[$player_id]['player_name'],
                'player_id' => $player_id,
                'card_type' => $this->card_types[$card['type']],
                'card_name' => $this->card_types[$card['type']]['name'],
                'card' => $card,
                'bubble' => $bubble_text
            ));
        }
    }

    function getBubbleTextCardPlayed($card)
    {
        $bubbleText = [
            self::GUARD      => clienttranslate('${opponent_name}, I think you are a ${guess_name}!'),
            self::PRIEST     => clienttranslate('${opponent_name}, please show me your card'),
            self::BARON      => clienttranslate('${opponent_name}, let`s compare our cards...'),
            self::HANDMAID   => clienttranslate('I`m protected for one turn'),
            self::PRINCE     => clienttranslate('${opponent_name}, you must discard your card'),
            self::CHANCELLOR => clienttranslate('I will take two more cards'),
            self::KING       => clienttranslate('${opponent_name}, we must swap our cards'),
            self::COUNTESS   => clienttranslate('I play the Countess'),
            self::PRINCESS   => clienttranslate('I discard the princess and I am out of the round'),
            self::SPY        => clienttranslate('I am watching you...'),
        ]; 

        return $bubbleText[$card['type']];
    }

    function getBubbleTextNothingHappens($card)
    {
        $bubbleText = [
            self::GUARD      => clienttranslate("I am not"),
            self::BARON      => clienttranslate("Our cards are identical, nothing happens!"),
        ]; 

        return $bubbleText[$card['type']];
    }

    function getBubbleTextOutOfTheRound($card)
    {
        $bubbleText = [
            self::GUARD      => clienttranslate('You got me!'),
            self::BARON      => clienttranslate('My ${card_name} was lower than ${opponent_name}`s card, so I`m out of the round!'),
            self::PRINCE     => clienttranslate('I discarded the Princess so I`m out of the round!'),
        ]; 

        return $bubbleText[$card['type']];
    }

    function getLogTextOutOfTheRound($card)
    {
        $logText = [
            self::GUARD      => clienttranslate('Out of the round: ${player_name} is actually a ${card_name}'),
            self::BARON      => clienttranslate('Out of the round: ${player_name} has the lower card, and discards a ${card_name}'),
            self::PRINCE     => clienttranslate('Out of the round: ${player_name} discards the Princess'),
            self::PRINCESS   => clienttranslate('Out of the round: ${player_name} plays a Princess and discards a ${card_name}'),
        ]; 

        return $logText[$card['type']];
    }

    function getLogTextCardReveal()
    {
        return clienttranslate('${player_name} reveals a ${card_name}');
    }

    function getLogTextCardExchange()
    {
        return clienttranslate('King: ${player_name} and ${player_name2} exchange their hand.');
    }

    /** 
     * If all other alive players are protected by Handmaid 
     */
    function notifyPlayCardWithNoPossibleTarget($card)
    {
        $player_id = self::getActivePlayerId();

        $possible_opponents = self::getObjectListFromDb("SELECT player_id FROM player WHERE player_id!='$player_id' AND player_protected='0' AND player_alive='1'", true);
        if (!empty($possible_opponents))
        {
            throw new feException("There are possible targets, but no opponent was selected");
        }
        
        $players = self::loadPlayersBasicInfos();

        self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays ${card_name} with no effect (no possible target)'), array(
            'i18n' => array('card_name'),
            'player_name' => $players[$player_id]['player_name'],
            'player_id' => $player_id,
            'card_type' => $this->card_types[$card['type']],
            'card_name' => $this->card_types[$card['type']]['name'],
            'card' => $card,
        ));
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
        foreach( $players as $player_id => $player)
        {
            $card = $this->cards->pickCard( 'deck', $player_id );    
            self::notifyPlayer( $player_id, 'newCardPrivate', clienttranslate('A new round begins: you draw a ${card_name}'), array(
                'i18n' => array('card_name'),
                'card' => $card,
                'card_name' => $this->card_types[$card['type']]['name'])
            );
        }

        // +1 card for active player
        $card = $this->cards->pickCard( 'deck', self::getActivePlayerId() );    
        self::notifyPlayer( self::getActivePlayerId(), 'newCardPrivate', clienttranslate('At the start of your turn, you draw a ${card_name}'), array(
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
            return;
        }

        //$next_player = self::getActivePlayerId(); //make testing easier with this line
        $next_player = self::getNextAlivePlayer();

        $this->gamestate->changeActivePlayer($next_player);
        //TODO - do you need to validate here?
        // ... draw 1 card
        $card = $this->cards->pickCard('deck', $next_player);
        $this->updateCardCount();

        self::giveExtraTime($next_player);

        self::DbQuery("UPDATE player SET player_protected='0' WHERE player_id='$next_player'");

        self::notifyPlayer($next_player, 'newCardPrivate', clienttranslate('At the start of your turn, you draw a ${card_name}'), array(
            'i18n' => array('card_name'),
            'card' => $card,
            'card_name' => $this->card_types[$card['type']]['name'])
        );

        //the current player will ignore this notification on client side.
        self::notifyAllPlayers('newCardPublic', '', array(
            'player_id' => $next_player,
        ));

        $this->gamestate->nextState('playerTurn');
    }

    function getNextAlivePlayer()
    {
        $current_player_id = self::getActivePlayerId();
        $next_player_table = self::getNextPlayerTable();
        $alive_players = [];
        $pid = $current_player_id;
        do {
            $pid = $next_player_table[$pid];
            $alive = (int) self::getUniqueValueFromDB("SELECT player_alive FROM player WHERE player_id='$pid'");
            if ($alive == 1) {
                $alive_players[] = $pid;
            }
        } while ($pid != $current_player_id);

        return count($alive_players) > 0 ? $alive_players[0] : null;
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
            $winning_card_type = $this->firstValue($this->cards->getCardsInLocation('hand', $winner_id))['type'];
            self::notifyAllPlayers('score', clienttranslate('${player_name} has the highest card (${card_type} - ${card_name}) and gains 1 favor token'), array(
                'i18n' => array('card_name'),
                'player_name' => $players[$winner_id]['player_name'],
                'player_id' => $winner_id,
                'card_type' =>  $this->card_types[$winning_card_type]['value'],
                'card_name' => $this->card_types[$winning_card_type]['name'],
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

    function firstValue(array $a)
    {
        foreach ($a as $v) { return $v; }
        return null;
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
    //TODO - can improve?
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
