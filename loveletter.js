/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * loveletter implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * loveletter.js
 *
 * loveletter user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
	"ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.loveletter", ebg.core.gamegui, {
        constructor: function(){
            console.log('loveletter constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            this.playerHand = null;
			
			this.deck = null;
			this.opponentHands = {};
			this.discards = {};
			this.discussionTimeout = {};
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function(gamedatas)
        {
            console.log("Starting game setup");
            
            // Cards dimensions depends on CSS media query
            this.card_width = Math.round(dojo.style('deck_1', 'width'));
            //alert('card width set to : '+ this.card_width);
            this.card_height = dojo.style('deck_1', 'height');

            dojo.query('.player_board_content .fa-star').addClass('fa-heart');
            dojo.query('.player_board_content .fa-star').removeClass('fa-star');

            var players_nbr = 0;

            // Setting up player boards
            for(var player_id in gamedatas.players)
            {
                players_nbr ++;
                var player = gamedatas.players[player_id];
                
                if (player_id != this.player_id)
                {
                    // Create opponent hand
                    this.opponentHands[player_id] = new ebg.stock();
                    this.opponentHands[player_id].create(this, $('playertablecard_'+player_id), 58, 80);
                    for(var i in this.gamedatas.card_types)
                    {
                        this.opponentHands[player_id].addItemType(i, 0, g_gamethemeurl+'img/backmini.jpg', i-1);                    
                    }
                    this.opponentHands[player_id].addItemType(0, 0, g_gamethemeurl+'img/backmini.jpg', 8); // back
                    this.opponentHands[player_id].autowidth = true;
                    this.opponentHands[player_id].selectable = 0;                    
                }
                
                if (player.alive == 0)
                {
                    this.disablePlayerPanel(player_id);
                    dojo.addClass('playertable_'+player_id, 'outOfTheRound');
                }
                
                if (player.protection == 1)
                {
                    this.setProtected(player_id);
                }
                
                // Played Cards number in discard
                this.discards[player_id] = new ebg.stock();
                if (this.card_width == 127)
                {
                    this.discards[player_id].create(this, $('discardcontent_'+player_id), 42, 42);
                }
                else
                {
                    this.discards[player_id].create(this, $('discardcontent_'+player_id), 30, 30);
                }
                this.discards[player_id].onItemCreate = dojo.hitch(this, 'setupNewCardIcon'); 
                for(var i in this.gamedatas.card_types)
                {
                    if (this.card_width == 127)
                    {
                        this.discards[player_id].addItemType(i, 0, g_gamethemeurl+'img/cardnumbers.png', i-1);
                    }
                    else
                    {
                        this.discards[player_id].addItemType(i, 0, g_gamethemeurl+'img/cardnumbers_small.png', i-1);
                    }
                }
                for(var i in gamedatas.discard[player_id])
                {
                    var card = gamedatas.discard[player_id][i];
                    this.discards[player_id].addToStockWithId(card.type, card.id);
                }
                this.discards[player_id].selectable = 0;
            }
            
            if (gamedatas.jester != 0)
            {
                this.setJester(gamedatas.jester);            
            }
            if (gamedatas.sycophant != 0)
            {
                this.setSycophant(gamedatas.sycophant);
            }
            
            if (players_nbr == 3)
            {
                dojo.addClass('ll_background', 'threeplayermode');
            }

			if (! this.isSpectator)
			{
			    // Player hand
                this.playerHand = new ebg.stock();
                this.playerHand.autowidth = true;
                
                this.playerHand.create(this, $('playertablecard_'+this.player_id), this.card_width, this.card_height);
                this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard'); 
                this.playerHand.apparenceBorderWidth = '3px';
                this.playerHand.selectable = 1;
                dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');
                        
                // Create card types
                for(var type_id in gamedatas.card_types)
                {
                    if (this.card_width == 127)
                    {
                        this.playerHand.addItemType(type_id, 0, g_gamethemeurl+'img/cards.jpg', type_id-1);
                    }
                    else
                    {
                        this.playerHand.addItemType(type_id, 0, g_gamethemeurl+'img/cards_small.jpg', type_id-1);
                    }
                }
            
                // Cards in player's hand
                for(var i in this.gamedatas.hand)
                {
                    var card = this.gamedatas.hand[i];
                    this.playerHand.addToStockWithId(card.type, card.id);
                }
            }
                        
            // Last played
            if (gamedatas.last > 0)
            {
                this.placeCardOnDiscard(0, 0, gamedatas.last, 'discard')
            }

            dojo.query('.selectable_playertable').connect('onclick', this, 'onSelectPlayer');

            this.updateCardCount(gamedatas.cardcount);
  
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log("Ending game setup");
        },
        
        onScreenWidthChange: function()
        {
            if (typeof this.card_width != 'undefined')
            {
                if (this.card_width != Math.round(dojo.style('deck_1', 'width')))
                {
                    //alert('card width = '+this.card_width+' while deck_1 width is '+dojo.style('deck_1', 'width'));
                    // The interface is too different : we must reload it entirely!
                    location.reload();
                }        
            }
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, args)
        {
            console.log('Entering state: '+stateName);
            
            switch(stateName)
            {
            case 'playerTurn':

                this.setUnprotected(this.getActivePlayerId());
                dojo.query('.currentactiveplayer').removeClass('currentactiveplayer');
                dojo.addClass('playertable_'+this.getActivePlayerId(), 'currentactiveplayer');

                break;
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style('my_html_block_id', 'display', 'block');
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName)
        {
            console.log('Leaving state: '+stateName);
            
            switch(stateName)
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style('my_html_block_id', 'display', 'none');
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function(stateName, args)
        {
            console.log('onUpdateActionButtons: '+stateName);
                      
            if (this.isCurrentPlayerActive())
            {            
                switch(stateName)
                {

                 case 'bishoptargeted':
                    
                    this.addActionButton('bishop_keep', _('Keep my card'), 'onBishopChoice'); 
                    this.addActionButton('bishop_discard', _('Discard my card'), 'onBishopChoice'); 
                    break;

/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton('button_1_id', _('Button 1 label'), 'onMyMethodToCall1'); 
                    this.addActionButton('button_2_id', _('Button 2 label'), 'onMyMethodToCall2'); 
                    this.addActionButton('button_3_id', _('Button 3 label'), 'onMyMethodToCall3'); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        // Update deck count, discard count, and players in hand displayed cards
        updateCardCount: function(cardcount)
        {
            // Deck
            dojo.query('.visibleDeck').removeClass('visibleDeck');
            if (cardcount.deck < 16)
            {
                dojo.addClass('deck_'+(toint(cardcount.deck)+1), 'visibleDeck');
            }
            $('deck_count').innerHTML = cardcount.deck;

            // Cards in hand            
            for(var player_id in this.gamedatas.players)
            {
                if (player_id != this.player_id)
                {
                    var nbr = 0;
                    if (cardcount.hand[player_id])
                    {
                        nbr = cardcount.hand[player_id];
                    }
                    
                    while(nbr < this.opponentHands[player_id].count())
                    {
                        this.opponentHands[player_id].removeFromStock(0);
                    }
                    while(nbr > this.opponentHands[player_id].count())
                    {
                        this.opponentHands[player_id].addToStock(0, 'deck');
                    }
                }
            }
        },
        
        // Place given card on discard pile, from given location
        placeCardOnDiscard: function(id, player_id, type, from, opponent_id)
        {
            var backx = this.card_width * (toint(type) - 1);
        
            dojo.place(this.format_block('jstpl_cardontable', {
                id: id,
                x: backx 
            }), 'discard');
            
            this.placeOnObject('cardontable_'+id, from);
            
            if (typeof opponent_id != 'undefined')
            {
                var anim = dojo.fx.chain([
                    this.slideToObject('cardontable_'+id, 'playertablename_'+opponent_id, 1000),
                    this.slideToObject('cardontable_'+id, 'discard', 1000)
             ]);
            }
            else
            {
                var anim = this.slideToObject('cardontable_'+id, 'discard', 1000);
            }
            anim.play();
            
            this.setupNewCard($('cardontable_'+id), type, id);
            
            if (player_id != 0)
            {
                this.discards[player_id].addToStockWithId(type, id, from);
            }
        },
        
        // Bubble management
        showDiscussion: function(player_id, text, delay, duration)
        {
            if (typeof delay == 'undefined')
            {   delay = 0;  }
            if (typeof duration == 'undefined')
            {   duration = 3000;  }
            
            if (delay > 0)
            {
                setTimeout(dojo.hitch(this, function() {  this.doShowDiscussion(player_id, text); }), delay);
            }
            else
            {
                this.doShowDiscussion(player_id, text);
            }

            if (this.discussionTimeout[player_id])
            {
                clearTimeout(this.discussionTimeout[player_id]);
                delete this.discussionTimeout[player_id];
            }

            
            this.discussionTimeout[player_id] = setTimeout(dojo.hitch(this, function() {  this.doShowDiscussion(player_id, ''); }), delay+duration);
        },
        doShowDiscussion: function(player_id, text)
        {
            if (text == '')
            {
                if (this.discussionTimeout[player_id])
                {   delete this.discussionTimeout[player_id]; }
            
                // Hide
                var anim = dojo.fadeOut({ node : 'discussion_bubble_'+player_id, duration:100 });
                dojo.connect(anim, 'onEnd', function() {
                    $('discussion_bubble_'+player_id).innerHTML = '';                
                });
                anim.play();
            }        
            else
            {
            
                $('discussion_bubble_'+player_id).innerHTML = text;
                dojo.style('discussion_bubble_'+player_id, 'display', 'block');
                dojo.style('discussion_bubble_'+player_id, 'opacity', 0);
                dojo.fadeIn({ node : 'discussion_bubble_'+player_id, duration:100 }).play();
            }
        },
        
        
        setupNewCard: function(card_div, card_type_id, card_id)
        {
            if (card_type_id != 0)
            {
                var card = this.gamedatas.card_types[card_type_id];
                var html = this.getCardTooltip(card_type_id, false);
           
                this.addTooltipHtml(card_div.id, html, 100);
                            
                dojo.place(this.format_block('jstpl_card_content', {
                                id:card_id, 
                                type: card_type_id,
                                name: _(card.name)
                           }), card_div.id);
            }
        },
        setupNewCardIcon: function(card_div, card_type_id, card_id)
        {
            if (card_type_id != 0)
            {
                var card = this.gamedatas.card_types[card_type_id];
                var html = this.getCardTooltip(card_type_id, false);
           
                this.addTooltipHtml(card_div.id, html, 100);
                            
                dojo.place(this.format_block('jstpl_card_content', {
                                id:card_id, 
                                type: card_type_id,
                                name: ''
                           }), card_div.id);
            }
        },
        
        getCardTooltip: function(type_id)
        {
            var html = "<div class='tooltip_wrap'>";
            
            html += '<h3>'+ _(this.gamedatas.card_types[type_id].name) +' ('+ this.gamedatas.card_types[type_id].value +')</h3>';
            html += '<hr/>';
            html += _(this.gamedatas.card_types[type_id].description);
            
            html += '</div>';
            
            return html;
        },
        
        setProtected: function(player_id)
        {
            dojo.style('player_protection_'+player_id, 'display', 'inline');   

            var anim = dojo.fx.chain([
                    dojo.fadeOut({ node: $('player_protection_'+player_id) }),
                    dojo.fadeIn({ node: $('player_protection_'+player_id) }),
                    dojo.fadeOut({ node: $('player_protection_'+player_id) }),
                    dojo.fadeIn({ node: $('player_protection_'+player_id) }),
                 ]);
            anim.play();
            
        },
        setUnprotected: function(player_id)
        {
            dojo.style('player_protection_'+player_id, 'display', 'none');   
        },

        setJester: function(player_id)
        {
            dojo.style('player_jester_'+player_id, 'display', 'inline');   

            var anim = dojo.fx.chain([
                    dojo.fadeOut({ node: $('player_jester_'+player_id) }),
                    dojo.fadeIn({ node: $('player_jester_'+player_id) }),
                    dojo.fadeOut({ node: $('player_jester_'+player_id) }),
                    dojo.fadeIn({ node: $('player_jester_'+player_id) }),
                 ]);
            anim.play();
            
        },
        setSycophant: function(player_id)
        {
            if (player_id != 0 && $('player_sycophant_'+player_id))
            {
                dojo.style('player_sycophant_'+player_id, 'display', 'inline');   

                var anim = dojo.fx.chain([
                        dojo.fadeOut({ node: $('player_sycophant_'+player_id) }),
                        dojo.fadeIn({ node: $('player_sycophant_'+player_id) }),
                        dojo.fadeOut({ node: $('player_sycophant_'+player_id) }),
                        dojo.fadeIn({ node: $('player_sycophant_'+player_id) }),
                     ]);
                anim.play();
            }
            else
            {
                dojo.query('.player_sycophant').style('display', 'none');
            }            
        },
        

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        isThereAtLeastOneSelectableOpponent: function()
        {
            for(var player_id in this.gamedatas.players)
            {
                if (player_id != this.player_id)
                {
                    if (dojo.hasClass('playertable_'+player_id, 'outOfTheRound'))
                    {   // Out of the round
                    }
                    else if (dojo.style('player_protection_'+player_id, 'display') == 'inline')
                    {
                        // Protected
                    }
                    else
                    {
                        return true;
                    }
                }            
            }        
            
            return false;
        },
        isThereAtLeastTwoSelectableOpponent: function()
        {
            var selectable = 0;
            for(var player_id in this.gamedatas.players)
            {
                if (player_id != this.player_id)
                {
                    if (dojo.hasClass('playertable_'+player_id, 'outOfTheRound'))
                    {   // Out of the round
                    }
                    else if (dojo.style('player_protection_'+player_id, 'display') == 'inline')
                    {
                        // Protected
                    }
                    else
                    {
                        selectable++;
                    }
                }            
            }        
            
            return (selectable>=2);
        },
        isThereAtLeastTwoSelectablePlayers: function()
        {
            var selectable = 0;
            for(var player_id in this.gamedatas.players)
            {
                {
                    if (dojo.hasClass('playertable_'+player_id, 'outOfTheRound'))
                    {   // Out of the round
                    }
                    else if (dojo.style('player_protection_'+player_id, 'display') == 'inline')
                    {
                        // Protected
                    }
                    else
                    {
                        selectable++;
                    }
                }            
            }        
            
            return (selectable>=2);
        },

        
        // Check if the (interface) condition has been met to play a card, and if yes play it
        checkIfCardCanBePlayed: function(bConfirmed)
        {
            if (typeof bConfirmed == 'undefined')
            {   bConfirmed = false; }
        
            var selection = this.playerHand.getSelectedItems();

            if (selection.length == 1)
            {
                var card = selection[0];
                var opponent_id = null;
                
                if (card.type == 1 || card.type == 12 || card.type == 14)
                {
                    // Must have chosen opponent + target card

                    opponents = this.getSelectedOpponent();
                    
                    if (opponents.length == 0 && this.isThereAtLeastOneSelectableOpponent())
                    {
                        this.showMessage(_("Please select a target opponent"), 'info');
                        return ;
                    }
                    
                    if (opponents.length > 1)
                    {
                        this.showMessage(_("Please select only one target opponent"), 'info');
                        return ;
                    }

                    var opponent_id = null;                    
                    if (opponents.length == 1)
                    {
                        var opponent_id = opponents[0];
                    }

                    if (this.isThereAtLeastOneSelectableOpponent())
                    {
                        if ($('guard_dialog'))
                        {   dojo.destroy('guard_dialog');  }
                        
                        var title = _('Guard : Guess who is ${player}?');
                        if (card.type == 14)
                        {   title = _('Bishop : Guess who is ${player}?');    }
                        
                        var guardDlg = new dijit.Dialog({ title: dojo.string.substitute(title , { player: this.gamedatas.players[opponent_id].name }) });

                        var html = "<div id='guard_dialog' style='max-width:500px;'>";


                        var cardlist = [
                            { num: 8, nam: _(this.gamedatas.card_types[8].name) },
                            { num: 7, nam: _(this.gamedatas.card_types[7].name) },
                            { num: 6, nam: _(this.gamedatas.card_types[6].name) },
                            { num: 5, nam: _(this.gamedatas.card_types[5].name) },
                            { num: 4, nam: _(this.gamedatas.card_types[4].name) },
                            { num: 3, nam: _(this.gamedatas.card_types[3].name) },
                            { num: 2, nam: _(this.gamedatas.card_types[2].name) },
                     ];
                        
                        if (toint(this.gamedatas.players_nbr) > 4)
                        {
                            cardlist = [
                                { num: 9, nam: _(this.gamedatas.card_types[14].name) },
                                { num: 8, nam: _(this.gamedatas.card_types[8].name) },
                                { num: 7, nam: _(this.gamedatas.card_types[7].name)+' / '+_(this.gamedatas.card_types[11].name) },
                                { num: 6, nam: _(this.gamedatas.card_types[6].name)+' / '+_(this.gamedatas.card_types[15].name) },
                                { num: 5, nam: _(this.gamedatas.card_types[5].name)+' / '+_(this.gamedatas.card_types[18].name) },
                                { num: 4, nam: _(this.gamedatas.card_types[4].name)+' / '+_(this.gamedatas.card_types[17].name) },
                                { num: 3, nam: _(this.gamedatas.card_types[3].name)+' / '+_(this.gamedatas.card_types[19].name) },
                                { num: 2, nam: _(this.gamedatas.card_types[2].name)+' / '+_(this.gamedatas.card_types[20].name) },
                                { num: 0, nam: (card.type == 14 ? _(this.gamedatas.card_types[13].name) + ' / ' : '') +  _(this.gamedatas.card_types[16].name) },
                         ];
                        }

                        for(var i in cardlist)
                        {
                            var num = cardlist[i].num;
                            var names = cardlist[i].nam;
                            
                            if (num == 9)
                            {   num = 14;   }
                            if (num == 0)
                            {   num = 13;   }
                        
                            html += '<div id="guardchoicewrap_'+num+'">'; 
                            html += '<a href="#" id="guardchoice_'+num+'" class="guardchoicelink">';
                            var backx = 42*(num-1);
                            html += '<div class="guardchoiceicon" style="background-position : -'+backx+'px -0px;"></div>';
                            html += '<div class="guardchoicename">'+names+'</div>';
                            html += '</a>';
                            html += '</div>';
                        }
                        
                        if (card.type == 14)
                        {
                            html += '<p style="font-size:60%">('+_('You cannot target a Guard with a Bishop')+')</p>';
                        }
                        else 
                        {
                            html += '<p style="font-size:60%">('+_('You cannot target a Guard with a guard')+')</p>';
                        }

                        html += "<br/><div style='text-align: center;'>";
                        html += "<a class='bgabutton bgabutton_gray' id='cancel_btn' href='#'><span>"+_("Cancel")+"</a>";
                        html += "</div></div>";

                        guardDlg.attr("content", html);
                        guardDlg.show();

                        dojo.connect($('cancel_btn'), 'onclick', this, function(evt)
                        {
                            evt.preventDefault();
                            guardDlg.hide();
                        });
                        
                        dojo.query('.guardchoicelink').connect('onclick', this, function(evt) {
                        
                            evt.preventDefault();
                            var guess_id = evt.currentTarget.id.substr(12);

                            this.ajaxcall("/loveletter/loveletter/playCard.html", { 
                                                                                    lock: true, 
                                                                                    card: card.id,
                                                                                    guess: guess_id,
                                                                                    opponent: opponent_id
                                                                                 },    this, function(result) {  }, function(is_error) { });        

                            
                            dojo.query('.selectedOpponent').removeClass('selectedOpponent');            

                            guardDlg.hide();                        
                        });

                        return ;
                    }
                    else
                    {
                        // Proceed to guard (with no effect!) 
                        if (!bConfirmed)   
                        {
                            this.confirmationDialog(_("All opponents are protected : are you sure you want to use this card with no effect?"), dojo.hitch(this, function() { 
                            
                                this.checkIfCardCanBePlayed(true);

                            }),dojo.hitch(this, function() {
                            
                                this.playerHand.unselectAll();
                            
                            })
                            
                            );
                            return ;
                        }
                    }

                }
                else if ( card.type == 19)
                {
                    // Must choose 1 or 2 opponents
                    // Note : MUST choose 2 opponents if 2 opponents are selectable

                    opponents = this.getSelectedOpponent();
                    
                    if (opponents.length == 0 && this.isThereAtLeastOneSelectableOpponent())
                    {
                        this.showMessage(_("Please select a target opponent"), 'info');
                        return ;
                    }

                    if (this.isThereAtLeastTwoSelectableOpponent())
                    {
                        if (opponents.length < 2)
                        {
                            this.showMessage(_("Please select two opponents"), 'info');
                            return ;
                        }
                    }

                    if (opponents.length > 2)
                    {
                        this.showMessage(_("Please select no more than two target opponents"), 'info');
                        return ;
                    }

                    var opponent_id = opponents.join(',');

                    if (! this.isThereAtLeastOneSelectableOpponent())
                    {
                        if (!bConfirmed)   
                        {
                            this.confirmationDialog(_("All opponents are protected : are you sure you want to use this card with no effect?"), dojo.hitch(this, function() { 
                            
                                this.checkIfCardCanBePlayed(true);

                            }),dojo.hitch(this, function() {
                            
                                this.playerHand.unselectAll();
                            
                            })
                            
                            );
                            return ;
                        }
                    
                    }                    

                
                }
                else if (card.type == 20)
                {
                    // Must choose 1 or 2 opponents
                    // Note : MUST choose 2 opponents if 2 opponents are selectable

                    opponents = this.getSelectedOpponent();
                    
                    if (opponents.length == 0 && this.isThereAtLeastOneSelectableOpponent())
                    {
                        this.showMessage(_("Please select a target opponent"), 'info');
                        return ;
                    }

                    // Must choose 2 players

                    if (this.isThereAtLeastTwoSelectablePlayers())
                    {
                        if (opponents.length < 2)
                        {
                            this.showMessage(_("Please select two players (may be yourself)"), 'info');
                            return ;
                        }
                    }

                    if (opponents.length > 2)
                    {
                        this.showMessage(_("Please select no more than two target opponents"), 'info');
                        return ;
                    }
                    
                    var opponent_id = opponents.join(',');

                    if (! this.isThereAtLeastOneSelectableOpponent())
                    {
                        if (!bConfirmed)   
                        {
                            this.confirmationDialog(_("All opponents are protected : are you sure you want to use this card with no effect?"), dojo.hitch(this, function() { 
                            
                                this.checkIfCardCanBePlayed(true);

                            }),dojo.hitch(this, function() {
                            
                                this.playerHand.unselectAll();
                            
                            })
                            
                            );
                            return ;
                        }
                    
                    }                    
                
                }
                else if (card.type == 2 || card.type == 3 || card.type == 6 || card.type == 11 || card.type ==16 )
                {
                    // Must have chosen opponent
                    opponents = this.getSelectedOpponent();
                    
                    if (opponents.length == 0 && this.isThereAtLeastOneSelectableOpponent())
                    {
                        this.showMessage(_("Please select a target opponent"), 'info');
                        return ;
                    }
                    
                    // Must choose exactly one opponent

                    if (opponents.length > 1)
                    {
                        this.showMessage(_("Please select only one target opponent"), 'info');
                        return ;
                    }

                    var opponent_id = null;                    
                    if (opponents.length == 1)
                    {
                        var opponent_id = opponents[0];
                    }
                    
                    if (! this.isThereAtLeastOneSelectableOpponent())
                    {
                        if (!bConfirmed)   
                        {
                            this.confirmationDialog(_("All opponents are protected : are you sure you want to use this card with no effect?"), dojo.hitch(this, function() { 
                            
                                this.checkIfCardCanBePlayed(true);

                            }),dojo.hitch(this, function() {
                            
                                this.playerHand.unselectAll();
                            
                            })
                            
                            );
                            return ;
                        }
                    
                    }                    
                }
                else if (card.type == 5 || card.type == 17)
                {
                    // Must have chosen opponent / yourself
                    opponents = this.getSelectedOpponent();
                    
                    if (opponents.length == 0 && this.isThereAtLeastOneSelectableOpponent())
                    {
                        this.showMessage(_("Please select a target opponent"), 'info');
                        return ;
                    }
                    
                    if (opponents.length > 1)
                    {
                        this.showMessage(_("Please select only one target opponent"), 'info');
                        return ;
                    }

                    var opponent_id = null;                    
                    if (opponents.length == 1)
                    {
                        var opponent_id = opponents[0];
                    }
                    
                    if (opponent_id === null)
                    {
                        this.showMessage(_("Please select a target opponent (or yourself)"), 'info');
                        return ;
                    }

                }
                else if (card.type == 4 || card.type == 7)
                {
                    // Immediate play
                }
                else if (card.type == 8)
                {
                    // Warn that this may kill yourself
                    if (! bConfirmed)
                    {
                        this.confirmationDialog(_("Playing the Princess will knock you out of the round. Are you sure?"), dojo.hitch(this, function() { 

                                    this.checkIfCardCanBePlayed(true);

                        

                        }),dojo.hitch(this, function() {
                        
                            this.playerHand.unselectAll();
                        
                        })
                        
                        );
                        return ;
                    }
                }
                else
                {
                }

                this.ajaxcall("/loveletter/loveletter/playCard.html", { 
                                                                        lock: true, 
                                                                        card: card.id,
                                                                        opponent: opponent_id,
                                                                        guess: -1
                                                                     },    this, function(result) {  }, dojo.hitch(this, function(is_error) { 
                                                                        this.playerHand.unselectAll();
                                                                     }));        

                
                dojo.query('.selectedOpponent').removeClass('selectedOpponent'); 
            }        
        },
        
        onPlayerHandSelectionChanged: function(evt)
        {
            var selection = this.playerHand.getSelectedItems();

            if (selection.length == 1)
            {
                if (this.checkAction('playCard'))
                {
                    this.checkIfCardCanBePlayed();
                }
            }
        },
        
        onSelectPlayer: function(evt)
        {
            dojo.stopEvent(evt);

            // playertable_<id>
            var player_id = evt.currentTarget.id.substr(12);
            
            if (this.checkAction('cardinalchoice', false))
            {
                // Cardinal choice

               this.ajaxcall("/loveletter/loveletter/cardinalchoice.html", { 
                                                                    lock: true, 
                                                                    choice: player_id
                                                                 },    this, function(result) {  }, dojo.hitch(this, function(is_error) { 
                                                                 }));        


                
                return ;
            }
            
            
            if (! this.checkAction('playCard'))
            {
                return ;
            }
            

            opponents = this.getSelectedOpponent();

            var bAlreadySelected = false;
            for(var i in opponents)
            {
                if (player_id == opponents[i])
                {   bAlreadySelected = true;    }
            }

            if (bAlreadySelected)
            {
                dojo.removeClass('playertable_'+player_id, 'selectedOpponent');
                this.checkIfCardCanBePlayed();
            }
            else
            {
                // Check out of the round
                if (dojo.hasClass('playertable_'+player_id, 'outOfTheRound'))
                {
                    this.showMessage(_("This player is out of the round"), 'error');
                    return ;
                }   
            
                // Check protection
                if (dojo.style('player_protection_'+player_id, 'display') == 'inline')
                {
                    this.showMessage(_("This player is protected and cannot be targeted by any card effect."), 'error');
                    return ;
                }
                
                if (player_id == this.player_id)
                {
                    // Possible only if current player has a Prince or Sycophant
                    if (dojo.query('#playertablecard_'+this.player_id+' .cardtype_5').length > 0 || dojo.query('#playertablecard_'+this.player_id+' .cardtype_17').length > 0 || dojo.query('#playertablecard_'+this.player_id+' .cardtype_20').length > 0 )
                    {
                        // Okay, there is a Prince on hand
                    }
                    else
                    {
                        return; // cannot select player as target
                    }
                    
                }
            
                if (dojo.query('#playertablecard_'+this.player_id+' .cardtype_19').length > 0 || dojo.query('#playertablecard_'+this.player_id+' .cardtype_20').length > 0 )
                {
                    // May select more than 1 card !
                } 
                else
                {                
                    dojo.query('.selectedOpponent').removeClass('selectedOpponent');
                }
                
                dojo.addClass('playertable_'+player_id, 'selectedOpponent');
                this.checkIfCardCanBePlayed();
            }
        },
        
        getSelectedOpponent: function()
        {
            var result = [];
            var opponents = dojo.query('.selectedOpponent').forEach(function(node) {
                result.push(node.id.substr(12));
            });

            return result;
        },
        
        onBishopChoice: function(evt)
        {
            var bDiscard = evt.currentTarget.id == 'bishop_discard';

            this.ajaxcall("/loveletter/loveletter/bishopChoice.html", { 
                                                                    lock: true, 
                                                                    choice: bDiscard
                                                                 },    this, function(result) {  }, dojo.hitch(this, function(is_error) { 
                                                                 }));        

        },
        
        /* Example:
        
        onMyMethodToCall1: function(evt)
        {
            console.log('onMyMethodToCall1');
            
            // Preventing default browser reaction
            dojo.stopEvent(evt);

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if (! this.checkAction('myAction'))
            {   return; }

            this.ajaxcall("/loveletter/loveletter/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function(result) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function(is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         });        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your loveletter.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log('notifications subscriptions setup');
            
            dojo.subscribe('cardPlayedLong', this, "notif_cardPlayed");
            dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            this.notifqueue.setSynchronous('cardPlayedLong', 3000);

            dojo.subscribe('updateCount', this, "notif_updateCount");
            dojo.subscribe('newCard', this, "notif_newCard");
            dojo.subscribe('endOfRoundExplanation', this, 'notif_endOfRoundExplanation');
            this.notifqueue.setSynchronous('endOfRoundExplanation', 500);

            dojo.subscribe('score', this, 'notif_score');
            this.notifqueue.setSynchronous('score', 600);

            dojo.subscribe('newRound', this, 'notif_newRound');
            dojo.subscribe('cardinalReveal', this, 'notif_cardinalReveal');

            dojo.subscribe('reveal', this, 'notif_reveal');
            dojo.subscribe('reveal_long', this, 'notif_reveal');
            this.notifqueue.setSynchronous('reveal', 2000);
            this.notifqueue.setSynchronous('reveal_long', 3000);

            dojo.subscribe('unreveal', this, 'notif_unreveal');
            
            dojo.subscribe('outOfTheRound', this, 'notif_outOfTheRound');

            dojo.subscribe('cardexchange', this, 'notif_cardexchange');

            dojo.subscribe('protected', this, 'notif_protected');
            dojo.subscribe('jester', this, 'notif_jester');
            dojo.subscribe('sycophant', this, 'notif_sycophant');
            dojo.subscribe('bishopGuessKeptCard', this, 'notif_bishopGuessKeptCard')

            dojo.subscribe('cardPlayedResult', this, 'notif_cardPlayedResult');

            dojo.subscribe('endOfRoundPause', this, 'notif_endOfRoundPause');
            this.notifqueue.setSynchronous('endOfRoundPause', 4000);

            //dojo.subscribe('endScore', this, 'notif_endScore');

            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            // this.notifqueue.setSynchronous('cardPlayed', 3000);
            // 
        },  
        
        // notif_endScore: function(notif)
        // {
        
        // },

        notif_endOfRoundPause: function(notif)
        {
        
        },
        
        notif_cardinalReveal: function(notif)
        {
            this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('${player_name} please show me your card.'), { player_name: '<span style="color:#'+this.gamedatas.players[notif.args.opponent_id].color+'">'+ this.gamedatas.players[notif.args.opponent_id].name+'</span>' }));
            this.showDiscussion(notif.args.opponent_id, _('Here it is.'), 2000);
        },
        
        notif_cardPlayed: function(notif)
        {
            if (this.player_id == notif.args.player_id)
            {
                // Current player played a card
                this.placeCardOnDiscard(notif.args.card.id, notif.args.player_id, notif.args.card.type, 'playertablecard_'+notif.args.player_id+'_item_'+notif.args.card.id, notif.args.opponent_id);
                this.playerHand.removeFromStockById(notif.args.card.id);
            }
            else
            {
                // Another player played a card
                this.placeCardOnDiscard(notif.args.card.id, notif.args.player_id, notif.args.card.type, 'playertablecard_'+notif.args.player_id , notif.args.opponent_id);
            }
            
            if (typeof notif.args.noeffect == 'undefined')
            {
                // Depending on the card played, have the correct discussion
                if (notif.args.card.type == 1 || notif.args.card.type == 12 || notif.args.card.type == 14 )
                {
                    // Guard : who are you?
                    this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('${player_name}, I think you are a ${guess}!'), { 
                        player_name: '<span style="color:#'+this.gamedatas.players[notif.args.opponent_id].color+'">'+ this.gamedatas.players[notif.args.opponent_id].name+'</span>',
                        guess: '<b>'+notif.args.guess_name+'</b>'
                    }));
                }
                else if (notif.args.card.type == 2 || notif.args.card.type == 19)
                {
                    // Priest : show your card

                    var delay = 0;
                    for(var i in notif.args.opponents)
                    {
                        var opponent_id = notif.args.opponents[i];
                        this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('${player_name} please show me your card.'), { player_name: '<span style="color:#'+this.gamedatas.players[opponent_id].color+'">'+ this.gamedatas.players[opponent_id].name+'</span>' }), delay);
                        this.showDiscussion(opponent_id, _('Here it is.'), delay+2000);
                        
                        delay += 2000;
                    }
                }
                else if (notif.args.card.type == 3 || notif.args.card.type == 11)
                {
                    this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('${player_name}, let`s compare our cards...'), { player_name: '<span style="color:#'+this.gamedatas.players[notif.args.opponent_id].color+'">'+ this.gamedatas.players[notif.args.opponent_id].name+'</span>' }));
                    this.showDiscussion(notif.args.opponent_id, _('Alright.'), 2000);
                }
                else if (notif.args.card.type == 4)
                {
                    this.showDiscussion(notif.args.player_id, _("I'm protected for one turn."));
                }
                else if (notif.args.card.type == 5)
                {
                    if (notif.args.player_id != notif.args.opponent_id)
                    {
                        this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('${player_name}, you must discard your card.'), { player_name: '<span style="color:#'+this.gamedatas.players[notif.args.opponent_id].color+'">'+ this.gamedatas.players[notif.args.opponent_id].name+'</span>' }));
                        this.showDiscussion(notif.args.opponent_id, _('Alright.'), 2000);
                    }
                    else
                    {
                        this.showDiscussion(notif.args.player_id, _('I play the Prince effect against myself and discard my card.'));
                    }
                }
                else if (notif.args.card.type == 6 )
                {
                    this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('${player_name}, we must exchange our hand.'), { player_name: '<span style="color:#'+this.gamedatas.players[notif.args.opponent_id].color+'">'+ this.gamedatas.players[notif.args.opponent_id].name+'</span>' }));
                    this.showDiscussion(notif.args.opponent_id, _('Alright.'), 2000);
                }
                else if (notif.args.card.type == 20)
                {
                    this.showDiscussion(notif.args.opponents[0], dojo.string.substitute(_('${player_name}, we must exchange our hand.'), { player_name: '<span style="color:#'+this.gamedatas.players[notif.args.opponents[1]].color+'">'+ this.gamedatas.players[notif.args.opponents[1]].name+'</span>' }));
                    this.showDiscussion(notif.args.opponents[1], _('Alright.'), 2000);
                }
                else if (notif.args.card.type == 18)
                {
                    this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('At the end of the round, my card value will be +1.'), { }));
                }
                else if (notif.args.card.type == 17)
                {
                    this.showDiscussion(notif.args.opponent_id, _("The next player card should target me..."));            
                }
                else if (notif.args.card.type == 15)
                {
                    this.showDiscussion(notif.args.player_id, dojo.string.substitute(_('If I am knocked out this round, I score one point.'), { }));
                }

            }            
        },
        
        notif_cardPlayedResult: function(notif)
        {
            if (notif.args.card_type == 1 || notif.args.card_type == 12)
            {
                if (toint(notif.args.success) == 1)
                {
                    this.showDiscussion(notif.args.player_id, _('You got me! I am out.'));
                }
                else if ( toint(notif.args.success) == 0)
                {
                    this.showDiscussion(notif.args.player_id, _('I am not.'));
                }
                else if ( toint(notif.args.success) == 2)
                {
                    this.showDiscussion(notif.args.player_id, _('In fact ... I am THE ASSASSIN!'));
                }
            }
            else if (notif.args.card_type == 14)
            {
                if (notif.args.success == 1)
                {
                    this.showDiscussion(notif.args.player_id, _('You got me!'));
                }
                else
                {
                    this.showDiscussion(notif.args.player_id, _('I am not.'));
                }
            }
            else if (notif.args.card_type == 3 || notif.args.card_type == 11)
            {
                if (notif.args.winner_id === null)
                {
                    // Tie!
                    this.showDiscussion(notif.args.player1, _("Our cards are identical, nothing happens!"));
                    this.showDiscussion(notif.args.player2, _("Our cards are identical, nothing happens!"));
                }
                else
                {
                    if (notif.args.card_type == 3)
                    {
                        this.showDiscussion(notif.args.winner_id, dojo.string.substitute(_('My card is higher than the ${card_name} of ${player_name}.'), { 
                            player_name: '<span style="color:#'+this.gamedatas.players[notif.args.loser_id].color+'">'+ this.gamedatas.players[notif.args.loser_id].name+'</span>',
                            card_name: '<b>'+notif.args.card_name+'</b>'
                        }));
                    }
                    else
                    {
                        this.showDiscussion(notif.args.winner_id, dojo.string.substitute(_('My card is lower than the ${card_name} of ${player_name}.'), { 
                            player_name: '<span style="color:#'+this.gamedatas.players[notif.args.loser_id].color+'">'+ this.gamedatas.players[notif.args.loser_id].name+'</span>',
                            card_name: '<b>'+notif.args.card_name+'</b>'
                        }));
                    }
                }
            }
        },
        
        notif_updateCount: function(notif)
        {
            console.log('notif_updateCount');
            console.log(notif);
            
            this.updateCardCount(notif.args.count);
        },    
        notif_newCard: function(notif) 
        {
            if (notif.args.from)
            {
                this.playerHand.addToStockWithId(notif.args.card.type, notif.args.card.id, 'playertable_'+notif.args.from);            
            }
            else
            {        
                this.playerHand.addToStockWithId(notif.args.card.type, notif.args.card.id, 'deck');            
            }
            
            if (notif.args.remove)
            {
                this.playerHand.removeFromStockById(notif.args.remove.id);
            }
        },
        notif_endOfRoundExplanation: function(notif)
        {
            // Show all hidden
            for(var i in notif.args.hands)
            {
                var card = notif.args.hands[i];
                
                if (card.location_arg != this.player_id)
                {
                    //alert(card.location_arg+' => '+card.type);
                    this.opponentHands[card.location_arg].removeAll();
                    this.opponentHands[card.location_arg].addToStock(card.type);
                }
            }
        },
        notif_score: function(notif)
        {
            this.scoreCtrl[notif.args.player_id].incValue(1);
            
            if (notif.args.type == 'remaining')
            {
                this.showDiscussion(notif.args.player_id, _("I'm the only one player remaining and I win this round!"), 0, 6000);
            }
            else if (notif.args.type == 'highest')
            {
                this.showDiscussion(notif.args.player_id, _("There is no more cards in the deck and I have the highest remaining card : I win this round!"), 0, 6000);            
            }
            else if (notif.args.type == 'highestdiscarded')
            {
                this.showDiscussion(notif.args.player_id, _("There is no more cards in the deck and several players are tied : I win this round because I discarded the highest total!"), 0, 6000);            
            }
            else if (notif.args.type == 'constable')
            {
                this.showDiscussion(notif.args.player_id, _("I am knocked out this round so thanks to Constable I score 1 point!"), 0, 3000);            
            }
            else if (notif.args.type == 'bishop')
            {
                this.showDiscussion(notif.args.player_id, _("I correctly guess the card with the Bishop and score 1 point!"), 0, 3000);            
            }
            else if (notif.args.type == 'jester')
            {
                this.showDiscussion(notif.args.player_id, _("I correctly guess this round winner and score 1 point thanks to the Jester!"), 0, 3000);            
            }
        },
        notif_newRound: function(notif)
        {
            // New round :
            for(var player_id in this.gamedatas.players)
            {
                if (player_id != this.player_id)
                {
                    this.opponentHands[player_id].removeAll();
                }
                this.discards[player_id].removeAll();
                
                this.setUnprotected(player_id);
            }
            if (! this.isSpectator)
            {
                this.playerHand.removeAll();
            }
            dojo.query('.cardontable').forEach(this.fadeOutAndDestroy);
            dojo.query('.player_jester').style('display', 'none');
            dojo.query('.player_sycophant').style('display', 'none');


            this.enableAllPlayerPanels();
            dojo.query('.outOfTheRound').removeClass('outOfTheRound');
        },
        notif_reveal: function(notif)
        {
            this.opponentHands[notif.args.player_id].removeAll();
            this.opponentHands[notif.args.player_id].addToStock(notif.args.card_type);
        },
        notif_unreveal: function(notif)
        {
            this.opponentHands[notif.args.player_id].removeAll();
            this.opponentHands[notif.args.player_id].addToStock(0);
        },
        notif_outOfTheRound: function(notif)
        {
            var player_id = notif.args.player_id;
            this.disablePlayerPanel(player_id);
            dojo.addClass('playertable_'+player_id, 'outOfTheRound');
        },
        notif_cardexchange: function(notif)
        {
            // Slide a card (back) from player 1 to player 2 (except if player 2 = current player)
            if (notif.args.player_2 != this.player_id)
            {
                this.opponentHands[notif.args.player_2].addToStock(0, 'playertable_'+notif.args.player_1);
                this.opponentHands[notif.args.player_2].removeFromStock(0);
            }
        
            // Slide a card (back) from player 2 to player 1 (except if player 1 = current player)
            if (notif.args.player_1 != this.player_id)
            {
                this.opponentHands[notif.args.player_1].addToStock(0, 'playertable_'+notif.args.player_2);
                this.opponentHands[notif.args.player_1].removeFromStock(0);
            }
            
        },
        notif_protected: function(notif)
        {
            this.setProtected(notif.args.player);
        },
        notif_jester: function(notif)
        {
            this.setJester(notif.args.player);
        },
        notif_sycophant: function(notif)
        {
            this.setSycophant(notif.args.player);
        },
        notif_bishopGuessKeptCard: function(notif)
        {
            //game log only.
        }

   });             
});
