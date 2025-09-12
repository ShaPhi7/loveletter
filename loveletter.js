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
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    g_gamethemeurl + "modules/bga-cards.js"
],
function (dojo, declare) {
    return declare("bgagame.loveletter", ebg.core.gamegui, {
        constructor: function () {
            console.log('loveletter constructor');

            this.playerHand = null;
            this.deck = null;
            this.opponentHands = {};
            this.discard = null;
            this.discussionTimeout = {};

            this.selectedOpponentId = null;
            this.selectedCardId = null; 
            this.selectedCardType = null;

            this.chancellorState = false;
            this.chancellorCardToKeep = null;
            this.chancellorCardToPlaceOnBottomOfDeck = null;

            const rootStyles = getComputedStyle(document.documentElement);
            this.cardWidth    = parseFloat(rootStyles.getPropertyValue('--card-width'));
            this.cardHeight   = parseFloat(rootStyles.getPropertyValue('--card-height'));

            Object.assign(this, window.CARD_CONSTANTS);
        },

        setup: function(gamedatas) {
            console.log("Love Letter setup", gamedatas);

            this.getGameAreaElement().insertAdjacentHTML("beforeend", `
                <div id="lvt-background"></div>
            `);

            this.getGameAreaElement().insertAdjacentHTML("beforeend", `
              <div id="lvt-play-area">
                <div id="lvt-play-area-grid">
                  <div class="lvt-player-area top-left" id="lvt-player-area-top-left"></div>
                  <div class="lvt-player-area top" id="lvt-player-area-top"></div>
                  <div class="lvt-player-area top-right" id="lvt-player-area-top-right"></div>
                  <div class="lvt-player-area left" id="lvt-player-area-left"></div>
                  <div id="lvt-center-area">
                      <div id="lvt-deck-area"></div>
                      <div id="lvt-badges-area"></div>
                  </div>
                  <div class="lvt-player-area right" id="lvt-player-area-right"></div>
                </div>
                <div class="lvt-player-area bottom" id="lvt-player-area-bottom"></div>
              </div>
            `);

            console.log(gamedatas);
            this.lvtPlayers = {};
            const playerIds = Object.keys(gamedatas.players);
            const totalPlayers = playerIds.length;
            this.deckManager = new CardManager(this, {
              getId: (card) => `lvt-card-${card.id}`,
              
              cardHeight: this.cardHeight,
              cardWidth: this.cardWidth,
              
              setupDiv: (card, div) => {
                div.classList.add('lvt-card-container');
              },
              
              setupFrontDiv: (card, div) => {
                    div.classList.add('lvt-card');
                        div.style.backgroundPosition = getCardSpriteBackgroundPosition(card, window.CARD_CONSTANTS);
                    div.id = `lvt-card-${card.id}-front`;
                },

              setupBackDiv: (card, div) => {
                  div.classList.add('lvt-card');
                      div.style.backgroundPosition = getCardSpriteBackgroundPosition("back", window.CARD_CONSTANTS);
                  div.id = `lvt-card-${card.id}-back`;
                },
            });

              this.handManager = new CardManager(this, {
              getId: (card) => `lvt-card-${card.id}`,
              
              cardHeight: this.cardHeight,
              cardWidth: this.cardWidth,
              
              setupDiv: (card, div) => {
                div.classList.add('lvt-card-container');
              },
              
              setupFrontDiv: (card, div) => {
                    div.classList.add('lvt-card');
                        div.style.backgroundPosition = getCardSpriteBackgroundPosition(card, window.CARD_CONSTANTS);
                    div.id = `lvt-card-${card.id}-front`;
                },

              setupBackDiv: (card, div) => {
                  div.classList.add('lvt-card');
                      div.style.backgroundPosition = getCardSpriteBackgroundPosition("back", window.CARD_CONSTANTS);
                  div.id = `lvt-card-${card.id}-back`;
                },
            });

            const layoutOrder = {
              2: ['bottom', 'top'],
              3: ['bottom', 'top-left', 'top-right'],
              4: ['bottom', 'left', 'top', 'right'],
              5: ['bottom', 'left', 'top-left', 'top', 'right'],
              6: ['bottom', 'left', 'top-left', 'top', 'top-right', 'right']
            };
            
            const playerPositions = layoutOrder[playerIds.length];
            const startIndex = gamedatas.playerorder.findIndex(id => id == this.player_id);
            const orderedPlayers = [];
            console.log(startIndex);
            for (let i = 0; i < gamedatas.playerorder.length; i++) {
              const rotatedIndex = (startIndex + i) % gamedatas.playerorder.length;
              orderedPlayers.push(gamedatas.playerorder[rotatedIndex]);
            }

            _this = this;
            Object.values(orderedPlayers).forEach((playerId, index) => {

            const player = gamedatas.players[playerId];
            const position = playerPositions[index];  

            document.getElementById(`lvt-player-area-${position}`).insertAdjacentHTML("beforeend", `
                  <div class="lvt-player-table" id="lvt-player-table-${player.id}">
                  <div id="lvt-discussion-bubble-${player.id}" class="lvt-discussion-bubble"></div>
                      <div class="lvt-player-table-name" id="lvt-player-table-name-${player.id}" style="color:#${player.color};">${player.name}</div>
                      <div class="lvt-player-table-card" id="lvt-player-table-card-${player.id}"></div>
                  </div>
              `);

              const playerTable = document.getElementById(`lvt-player-table-${player.id}`);
              playerTable.addEventListener('click', function(event) {
                if (playerTable.classList.contains('out-of-the-round')) return;

                const tablePlayerId = this.id.replace('lvt-player-table-', '');
                if (tablePlayerId === String(_this.player_id)) {
                  // Check if click was in your own card area
                  const cardArea = document.getElementById('lvt-player-table-card-' + tablePlayerId);
                  if (cardArea.contains(event.target) && event.target !== cardArea) {
                    return;
                  }
                }

                if (this.classList.contains('selected')) {
                  _this.removePlayerSelections();
                  return;
                }

                _this.removePlayerSelections();
                this.classList.add('selected');

                // Store selected opponent (even if it's yourself)
                // this.selectedOpponentId = tableId;
                // (If this is inside a class, use the right scope)
                _this.onSelectPlayer(tablePlayerId);
              });
            });

            this.playerHand = new LineStock(this.handManager, document.getElementById('lvt-player-table-card-' + this.player_id), {});
            this.playerHand.setSelectionMode('single');
            this.playerHand.onSelectionChange = (selectedCards) => {
              if (this._suppressUnselectCallback) return;
              if (selectedCards.length > 0) {
                this.onSelectCard(selectedCards[0]);
              }
              else
              {
                this.removeCardSelections();
              }
            };

            const handValues = Object.values(gamedatas.hand);
            handValues.forEach(card => {
              console.log("Adding card to player table", card);
              this.playerHand.addCard(card);
              this.playerHand.setCardVisible(card, true);
            });

            Object.values(gamedatas.players).forEach(player => {
              if (player.id != this.player_id) {
                const opponentHand = new HandStock(this.handManager, document.getElementById('lvt-player-table-card-' + player.id), {});
                opponentHand.setSelectionMode('single');
                const opponentHandSize = gamedatas.cardcount.hand[player.id];
                  for (let i = 0; i < opponentHandSize; i++) {
                    const fakeCard = {
                      id: `${player.id}-fake-${i}`,
                    };
                    opponentHand.addCard(fakeCard);
                    opponentHand.setCardVisible(fakeCard, false);
                  }
                this.opponentHands[player.id] = opponentHand;
              }

              if (player.alive == 0)
              {
                this.setOutOfTheRound(player.id);
              }
              
              if (player.protection == 1)
              {
                this.setProtected(player.id);
              }

              if (player.spied == 1)
              {
                this.setSpied(player.id);
              }

            });

          this.deck = new Deck(this.deckManager, document.getElementById('lvt-deck-area'), {
            cardNumber: gamedatas.deck.length,
            counter: {
                  position: 'center',
                  extraClasses: 'text-shadow',
                  hideWhenEmpty: false,
              },
            // onCardClick: (card) => {
            //     console.log("Deck clicked", card);
            //     this.handleDeckClick();
            //   },
          });

          this.discard = new ManualPositionStock(this.handManager, document.getElementById('lvt-badges-area'), {}, function (element, cards, card, stock) {
            // Example: stack all cards at 0,0
            cards.forEach((c, i) => {
                const cardDiv = stock.getCardElement(c);
                cardDiv.style.zIndex = i; // stack order
            });
          });

          // Testing only
          // this.deck.element.addEventListener('click', (event) => {
          //     this.handleDeckClick();
          // });

          buildPlayedCardBadges(gamedatas);
          this.setupNotifications();

        },

      //   handleDeckClick: function() {
      //     // console.log("Deck clicked!");
      //     // const othercard = this.playerHand.getCards()[0];
      //     const card = {
      //       id: 12345,
      //       type: 25,
      //       type_arg: 25
      //     };
      //     // this.playerHand.addCard(card, { fromStock: this.deck });
      //     // this.discard.addCard(othercard, { fromStock: this.playerHand });

      //   //this is how to flip the card
      //   opponentStock = this.opponentHands[Object.keys(this.opponentHands)[0]];
      //   opponentCard = opponentStock.getCards()[0];
      //   // Object.assign(opponentCard, {
      //   //   type: 25,
      //   //   type_arg: 25
      //   // });
      //   // console.log("Adding card to opponent hand", opponentCard);

      //   //this.opponentHands[Object.keys(this.opponentHands)[0]].setCardVisible(opponentCard, true);
      //   Object.assign(opponentCard, {
      //       type: this.PRINCE,
      //       type_arg: this.PRINCE
      //   });
      //   this.discard.addCard(opponentCard, {
      //       fromStock: opponentStock,
      //       updateInformations: {
      //           id: opponentCard.id,
      //           type: this.PRINCE,
      //           type_arg: this.PRINCE
      //       },
      //       visible: true
      //   });
      // },

        // handleHandClick: async function(card) {
        //   console.log("Hand clicked!", card);
        //   const cardElement = this.handManager.getCardElement(card); // your card object

        //   const opponentHandElementId = this.opponentHands[Object.keys(this.opponentHands)[0]].element.id;
        //   const opponentId = opponentHandElementId.replace('lvt-player-table-card-', '');
        //   this.opponentHands[Object.keys(this.opponentHands)[0]].element.classList.add('highlight');
        //   document.getElementById(`lvt-player-table-${opponentId}`).classList.add('highlight');

        //   await this.discard.addCard(card, { fromStock: this.playerHand });

        //   cardElement.classList.add('fade-out');
        //   const allDescendants = cardElement.querySelectorAll('*');
        //   for (const descendant of allDescendants) {
        //     descendant.classList.add('fade-out');
        //   }

        //   setTimeout(() => {
        //     this.discard.removeCard(card);
        //   }, 2000);

        // },

        resetActions: function () { //TODO - needed?
          let e = document.getElementById("pagemaintitletext");
          e.innerHTML = "";
          this.removeActionButtons();
          KvBoard.removeTargets();
        },

        setInvite: function (pInvite) {
          let e = document.getElementById("pagemaintitletext");
          e.innerHTML = pInvite;
        },

        doChancellorAction: function()
        {
          if (!this.chancellorCardToKeep)
          {
            this.chancellorCardToKeep = this.playerHand.selectedCards[0];
            const cardElement = this.playerHand.getCardElement(this.chancellorCardToKeep);
            
            if (cardElement) {
              dojo.addClass(cardElement, 'keep');
            }

            this.setInvite(_("You must choose which card to place on the bottom of the deck"));
            return;
          }

          if (this.chancellorCardToKeep.id == this.selectedCardId)
          {
            this.removeCardSelections();
            this.showMessage(_("You must choose a different card to the one that you chose to keep."), "error");
            return;
          }

          this.chancellorCardToPlaceOnBottomOfDeck = this.selectedCardId;
          this.bgaPerformAction('actionChancellor', { keep: Number(this.chancellorCardToKeep.id), bottom: Number(this.chancellorCardToPlaceOnBottomOfDeck) });
        },

        onEnteringState: function(stateName, args) {
            if (stateName === 'chancellor') {
              this.deselect();
              this.chancellorState = true;  
            }
        },

        onLeavingState: function(stateName, args) {
            if (stateName === 'chancellor') {
              this.deselect();
              this.chancellorState = false;
            }
        },

        onSelectPlayer: function(playerId) {
          this.selectedOpponentId = playerId;
            this.playCardOrShowMessage();
        },

        onSelectCard: function(card) {
          this.selectedCardId = card ? Number(card.id) : null;
          this.selectedCardType = card ? Number(card.type) : null;
          if (this.chancellorState)
          {
            this.doChancellorAction();
          }
          else
          {
            this.playCardOrShowMessage();
          }
        },

        playCardOrShowMessage: function()
        {     
          if (this.gamedatas.gamestate.active_player != this.player_id) {
            this.showMessage(_("It is not your turn."), "error");
            this.deselect();
            return;
          }

          if (this.chancellorState)
          {
            this.showMessage(_("You must a select cards."), "error");
            this.removePlayerSelections();
            return;
          }

          if (!this.selectedCardId) {
            this.showMessage(_("Please select a card to play."), "info");
            return;
          }

          const cardType = this.selectedCardType;
          const requiresOpponent = [this.GUARD, this.PRIEST, this.BARON, this.PRINCE, this.KING].includes(cardType);
          const canTargetSomeone = Object.values(this.gamedatas.players).some(player => 
              player.id != this.player_id &&
              player.alive == 1 &&
              player.protection != 1
          );
          if (!requiresOpponent
            || !canTargetSomeone)
          {
            //TODO - add dialog to warn user they are about to target nobody
            this.selectedOpponentId = null; // Clear opponent selection for non-targeting cards
          }
          else
          {
            if (!this.selectedOpponentId)
            {
              this.showMessage(_("Please select a player to target."), "info");
              return;
            }

            if (this.selectedOpponentId == this.player_id
              && cardType !== this.PRINCE
              && cardType !== this.KING)
            {
              this.showMessage(_("You cannot target yourself with this card."), "error");
              this.deselect();
              return;
            }

            // Check out of the round
            if(dojo.hasClass('lvt-player-table-' + this.selectedOpponentId, 'out-of-the-round'))
            {
              this.showMessage( _("This player is out of the round"), 'error' );
              this.deselect();
              return;
            }   
        
            // Check protection
            if(dojo.style('lvt-player-table-' + this.selectedOpponentId, 'protected'))
            {
              this.showMessage( _("This player is protected and cannot be targeted by any card effect."), 'error' );
              this.deselect();
              return;
            }
          }
          if (this.selectedCardType === this.GUARD
            || this.selectedCardType === this.PRINCESS
            || (this.selectedCardType === this.PRINCE
              && this.playerHand.getCards().some(c => c.type === this.PRINCESS))
            )
          {
            this.showConfirmationDialog();
          }  
          else
          {
            this.playCard(this.selectedCardId, -1, this.selectedOpponentId);
          }
        },

        showConfirmationDialog: function() {
          if (this.selectedCardType === this.PRINCESS
            || this.selectedCardType === this.PRINCE
          ) {
          this.confirmationDialog(_("Discarding the Princess will knock you out of the round. Are you sure?"),
          ( () => {  
            this.playCard(this.selectedCardId, -1, this.selectedOpponentId);
            }
          ),
          (
            () => {
              this.deselect();
            }
          ))
          }
          else
          {
            const rootStyles = getComputedStyle(document.documentElement);
            const SPRITE_WIDTH_ORIGINAL = parseFloat(rootStyles.getPropertyValue('--badge-sprite-width'));
            const SPRITE_HEIGHT_ORIGINAL = parseFloat(rootStyles.getPropertyValue('--badge-sprite-height'));
            const BADGE_WIDTH = 36;
            const BADGE_HEIGHT = 36;
            
            if( $('guard_dialog') )
            {   dojo.destroy( 'guard_dialog' );  }
            
            var title = _('Who is ${player}?');
            
            var guardDlg = new dijit.Dialog({ title: dojo.string.substitute( title , { player: this.gamedatas.players[ this.selectedOpponentId ].name } ) });

            var html = "<div id='guard_dialog' style='max-width:500px;'>";

            var cardlist = [
                  {id: this.PRINCESS,   num: this.gamedatas.card_types[this.PRINCESS   ].value, nam: _(this.gamedatas.card_types[ this.PRINCESS ].name) },
                  {id: this.COUNTESS,   num: this.gamedatas.card_types[this.COUNTESS   ].value, nam: _(this.gamedatas.card_types[ this.COUNTESS ].name) },
                  {id: this.KING,       num: this.gamedatas.card_types[this.KING       ].value, nam: _(this.gamedatas.card_types[ this.KING ].name) },
                  {id: this.CHANCELLOR, num: this.gamedatas.card_types[this.CHANCELLOR ].value, nam: _(this.gamedatas.card_types[ this.CHANCELLOR ].name) },
                  {id: this.PRINCE,     num: this.gamedatas.card_types[this.PRINCE     ].value, nam: _(this.gamedatas.card_types[ this.PRINCE ].name) },
                  {id: this.HANDMAID,   num: this.gamedatas.card_types[this.HANDMAID   ].value, nam: _(this.gamedatas.card_types[ this.HANDMAID ].name) },
                  {id: this.BARON,      num: this.gamedatas.card_types[this.BARON      ].value, nam: _(this.gamedatas.card_types[ this.BARON ].name) },
                  {id: this.PRIEST,     num: this.gamedatas.card_types[this.PRIEST     ].value, nam: _(this.gamedatas.card_types[ this.PRIEST ].name) },
                  {id: this.SPY,        num: this.gamedatas.card_types[this.SPY        ].value, nam: _(this.gamedatas.card_types[ this.SPY ].name) },
              ];

            for( var i in cardlist )
            {
                var num = cardlist[i].num;
                var names = cardlist[i].nam;
            
                html += '<div id="guardchoicewrap_'+num+'">'; 
                html += `<a href="#" class="guardchoicelink" data-id="${cardlist[i].id}">`;
                var xOffset = -(num * SPRITE_WIDTH_ORIGINAL * (BADGE_HEIGHT / SPRITE_HEIGHT_ORIGINAL));
                html += `<div class="guardchoiceicon" style="background-size: auto ${BADGE_HEIGHT}px; background-position: ${xOffset}px 0px; width: ${BADGE_WIDTH}px; height: ${BADGE_HEIGHT}px;"></div>`;
                html += '<div class="guardchoicename">'+names+'</div>';
                html += '</a>';
                html += '</div>';
            }

            html += '<p style="font-size:60%">('+_('You cannot target a Guard with a guard')+')</p>';
            html += "<br/><div style='text-align: center;'>";
            html += "<a class='bgabutton bgabutton_gray' id='cancel_btn' href='#'><span>"+_("Cancel")+"</a>";
            html += "</div></div>";

            guardDlg.attr("content", html );
            guardDlg.show();

            dojo.connect( $('cancel_btn'), 'onclick', this, function( evt )
            {
                evt.preventDefault();
                guardDlg.hide();
                this.deselect();
            } );
            
            dojo.query( '.guardchoicelink' ).connect( 'onclick', this, function( evt ) {
                evt.preventDefault();
                var guess_id = parseInt(evt.currentTarget.getAttribute('data-id'));
                console.log(guess_id);

                this.playCard(this.selectedCardId, guess_id, this.selectedOpponentId)
                dojo.query( '.selectedOpponent' ).removeClass( 'selectedOpponent' );            

                guardDlg.hide();                        
            } );

            return ;
          }
        },

        playCard: function(card, guess_id, opponent_id)
        {
          console.log("Playing card", card, "guess_id:", guess_id, "opponent_id:", opponent_id);
          this.ajaxcall( "/loveletter/loveletter/playCard.html", { 
                                                      lock: true, 
                                                      card: card,
                                                      guess: guess_id,
                                                      opponent: opponent_id
                                                    },    this, function( result ) {  }, function( is_error) { } );  
          this.deselect();
        },

        deselect: function()
        {
          this.removeCardSelections();
          this.removePlayerSelections();
          this.removeChancellorSelections();
        },

        removeCardSelections: function()
        {
          this.selectedCardId = null;
          this.selectedCardType = null;
          this._suppressUnselectCallback = true;
          this.playerHand.unselectAll();
          this._suppressUnselectCallback = false;
        },

        removePlayerSelections: function()
        {
          this.selectedOpponentId = null;
          const playerTables = Array.from(document.querySelectorAll('.lvt-player-table'));
          playerTables.forEach(pt => pt.classList.remove('selected'));
        },

        removeChancellorSelections: function()
        {
          this.chancellorCardToKeep = null;
          this.chancellorCardToPlaceOnBottomOfDeck = null;
        },

        // Bubble management
        showDiscussion: function(notif)
        {
          if (!notif.args.bubble)
          {
            return;
          }

          const opponent = notif.args.opponent_id ? this.gamedatas.players[notif.args.opponent_id] : null;
          var player_id = notif.args.player_id ? notif.args.player_id : notif.args.player1;
          var delay = notif.args.delay;
          var duration = notif.args.duration;

          text = dojo.string.substitute( notif.args.bubble, {
          opponent_name: opponent ? `<b><span style="color:#${opponent.color}">${opponent.name}</span></b>` : '',
          guess_name: notif.args.guess_name ?  `<b>${notif.args.guess_name}</b>` : '',
          card_name: notif.args.card_name ?  `<b>${notif.args.card_name}</b>` : '',
          });

          if( typeof delay == 'undefined' )
          {   delay = 0;  }
          if( typeof duration == 'undefined' )
          {   duration = 3000;  }
          
          if( delay > 0 )
          {
              setTimeout( dojo.hitch( this, function() {  this.doShowDiscussion( player_id, text ); } ), delay );
          }
          else
          {
              this.doShowDiscussion( player_id, text );
          }

          if( this.discussionTimeout[ player_id ] )
          {
              clearTimeout( this.discussionTimeout[ player_id ] );
              delete this.discussionTimeout[ player_id ];
          }

          
          this.discussionTimeout[ player_id ] = setTimeout( dojo.hitch( this, function() {  this.doShowDiscussion( player_id, '' ); } ), delay+duration );
        },
        doShowDiscussion: function( player_id, text )
        {
            if( text == '' )
            {
                if( this.discussionTimeout[ player_id ] )
                {   delete this.discussionTimeout[ player_id ]; }
            
                // Hide
                var anim = dojo.fadeOut( { node : 'lvt-discussion-bubble-' + player_id, duration:100 } );
                dojo.connect( anim, 'onEnd', function() {
                    $('lvt-discussion-bubble-' + player_id).innerHTML = '';
                } );
                anim.play();
            }        
            else
            {
                $('lvt-discussion-bubble-' + player_id).innerHTML = text;
                dojo.style( 'lvt-discussion-bubble-' + player_id, 'display', 'block' );
                dojo.style( 'lvt-discussion-bubble-' + player_id, 'opacity', 0 );
                dojo.fadeIn( { node : 'lvt-discussion-bubble-' + player_id, duration:100 } ).play();
            }
        },

        updateUiForCardPlayed(id, type, playerId, value)
        {
          let discardedCard = {};

          if (this.player_id == playerId)
          {
            Object.assign(discardedCard, {
              id: id,
              type: type,
            });

            this.discard.addCard(discardedCard, { fromStock: this.playerHand, position: { x: 0, y: 0 }});
          }
          else
          {                        
            const opponentHand = this.opponentHands[playerId];
            let fakeCardId = opponentHand.getCards().some(c => String(c.id).includes('fake-1')) ? '1' : '0';

            Object.assign(discardedCard, {
              id: `${playerId}-fake-${fakeCardId}`,
              type: type
            });

            this.discard.addCard(discardedCard, {
                fromStock: opponentHand,
                updateInformations: true,
                visible: true,
                position: { x: 0, y: 0 }
            });
          }
          const cardElement = this.handManager.getCardElement(discardedCard);
          cardElement.classList.add('fade-out');
            const allDescendants = cardElement.querySelectorAll('*');
            for (const descendant of allDescendants) {
              descendant.classList.add('fade-out');
          }
          setTimeout(() => {
            this.discard.removeCard(discardedCard);
            markBadgeAsPlayed(value);
          }, 3000);

          if (type == this.HANDMAID)
          {
            this.setProtected(playerId);
          }

          if (type == this.SPY)
          {
            this.setSpied(playerId);
          }
        },

        setOutOfTheRound: function(playerId)
        {
          dojo.addClass( 'lvt-player-table-' + playerId, 'out-of-the-round' );
        },

        setProtected: function(playerId)
        {
          dojo.addClass( 'lvt-player-table-' + playerId, 'protected' );
        },

        setSpied: function(playerId)
        {
          dojo.addClass( 'lvt-player-table-' + playerId, 'spied' );
        },

        unsetPlayerStatuses: function()
        {
          Object.keys(this.gamedatas.players).forEach(playerId => {
            dojo.removeClass('lvt-player-table-' + playerId, 'out-of-the-round');
            dojo.removeClass('lvt-player-table-' + playerId, 'protected');
            dojo.removeClass('lvt-player-table-' + playerId, 'spied');
          });
        },

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            dojo.subscribe( 'newCardPrivate', this, "notif_newCardPrivate" );
            dojo.subscribe( 'newCardPublic', this, "notif_newCardPublic" );
            dojo.subscribe( 'newCardPrivateQuick', this, "notif_newCardPrivate" );
            dojo.subscribe( 'newCardPublicQuick', this, "notif_newCardPublic" );
            this.notifqueue.setIgnoreNotificationCheck( 'newCardPublic', (notif) => (notif.args.player_id == this.player_id) );
            this.notifqueue.setIgnoreNotificationCheck( 'newCardPublicQuick', (notif) => (notif.args.player_id == this.player_id) );
            this.notifqueue.setSynchronous( 'newCardPrivate', 3000 );
            this.notifqueue.setSynchronous( 'newCardPublic', 3000 );
            this.notifqueue.setSynchronous( 'newCardPrivateQuick', 300 );
            this.notifqueue.setSynchronous( 'newCardPublicQuick', 300 );

            dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            this.notifqueue.setSynchronous( 'cardPlayed', 3000 );

            dojo.subscribe( 'reveal', this, 'notif_reveal' );
            dojo.subscribe( 'reveal_long', this, 'notif_reveal' );
            this.notifqueue.setIgnoreNotificationCheck( 'reveal', (notif) => (notif.args.player_id == this.player_id) ); //TODO
            this.notifqueue.setSynchronous( 'reveal_long', 3000 );

            dojo.subscribe( 'cardexchange', this, 'notif_cardexchange' );
            dojo.subscribe( 'cardexchange_opponents', this, 'notif_cardexchange_opponents' );
            this.notifqueue.setSynchronous( 'cardexchange', 2000 );
            this.notifqueue.setSynchronous( 'cardexchange_opponents', 1000 );

            dojo.subscribe( 'discardCard', this, 'notif_discardCard' );
            this.notifqueue.setSynchronous( 'discardCard', 3000 );

            dojo.subscribe( 'chancellor_draw', this, 'notif_chancellor_draw' );
            dojo.subscribe( 'chancellor_bury', this, 'notif_chancellor_bury' );
            dojo.subscribe( 'chancellor_draw_public', this, "notif_chancellor_draw_public" );
            dojo.subscribe( 'chancellor_bury_public_first', this, "notif_chancellor_bury_public_first" );
            dojo.subscribe( 'chancellor_bury_public_second', this, "notif_chancellor_bury_public_second" );
            this.notifqueue.setIgnoreNotificationCheck( 'chancellor_draw_public', (notif) => (notif.args.player_id == this.player_id) );
            this.notifqueue.setIgnoreNotificationCheck( 'chancellor_bury_public_first', (notif) => (notif.args.player_id == this.player_id) );
            this.notifqueue.setIgnoreNotificationCheck( 'chancellor_bury_public_second', (notif) => (notif.args.player_id == this.player_id) );
            this.notifqueue.setSynchronous( 'chancellor_draw_public', 3000 );
            this.notifqueue.setSynchronous( 'chancellor_bury', 3000 );
            this.notifqueue.setSynchronous( 'chancellor_bury_public_first', 1000 );
            this.notifqueue.setSynchronous( 'chancellor_bury_public_second', 1000 );

            dojo.subscribe( 'score', this, 'notif_score' );
            this.notifqueue.setSynchronous( 'score', 4000 );

            dojo.subscribe( 'simpleNote', this, 'notif_simpleNote' );
            this.notifqueue.setSynchronous( 'simpleNote', 2000 );

            dojo.subscribe( 'outOfTheRound', this, 'notif_outOfTheRound' );
            this.notifqueue.setSynchronous( 'outOfTheRound', 2000 );
            dojo.subscribe( 'newRound', this, 'notif_newRound' );
        },

        notif_score: function(notif)
        {
          //TODO - show cards revealed at end of the round
          this.showDiscussion(notif);
          this.scoreCtrl[notif.args.player_id].incValue(1);
        },

        notif_simpleNote: function(notif)
        {
          if (notif.args.bubble)
          {
            this.showDiscussion(notif);
          }
          //also adds a game log - this happens automatically.
        },

        notif_newRound: function(notif)
        {
          Object.values(this.opponentHands).forEach(hand => hand.removeAll());
          this.playerHand.removeAll();
          this.deck.removeAll();
          this.discard.removeAll();

          this.unsetPlayerStatuses();

          document.querySelectorAll('.lvt-card-badge.played').forEach(badge => {
            badge.classList.remove('played');
          });

          this.deck.setCardNumber(this.gamedatas.fulldeck, null);
          this.deck.shuffle();

          Object.keys(this.gamedatas.players).forEach(pid => {
            const bubble = document.getElementById(`lvt-discussion-bubble-${pid}`);
            if (bubble) bubble.innerHTML = '';
          });

          this.deselect();
        },

        notif_outOfTheRound: function(notif)
        {
          var player_id = notif.args.player_id;
          this.showDiscussion(notif);
          this.setOutOfTheRound(player_id);
          if (notif.args.card)
          {
            this.updateUiForCardPlayed(notif.args.card.id, notif.args.card.type, player_id, notif.args.card_type.value);
          }
        },

        notif_discardCard: function( notif )
        {
          this.showDiscussion(notif);
          this.updateUiForCardPlayed(notif.args.card.id, notif.args.card.type, notif.args.player_id, notif.args.card_type.value);
        },

        notif_chancellor_draw_public: function( notif )
        {
          let card = {
            id: `${notif.args.player_id}-fake-1` //the 'lvt-card' bit is added in the getId function of the HandStock
          };

          let card2 = {
            id: `${notif.args.player_id}-fake-2` //the 'lvt-card' bit is added in the getId function of the HandStock
          };

          const opponentHand = this.opponentHands[notif.args.player_id];
        
          opponentHand.addCard(card, { fromStock: this.deck });
          opponentHand.setCardVisible(card, false);

          opponentHand.addCard(card2, { fromStock: this.deck });
          opponentHand.setCardVisible(card2, false);
        },

        notif_chancellor_draw: function( notif )
        {
          if (notif.args.card)
          {
            let card = {};
            Object.assign(card, {
              id: notif.args.card.id,
              type: notif.args.card.type,
            });

            this.playerHand.addCard(card, { fromStock: this.deck });
            this.playerHand.setCardVisible(card, true);
          }
          
          if (notif.args.card_2)
          {
            let card2 = {};
            Object.assign(card2, {
              id: notif.args.card_2.id,
              type: notif.args.card_2.type,
            });

            this.playerHand.addCard(card2, { fromStock: this.deck });
            this.playerHand.setCardVisible(card2, true);
          }
        },

        notif_chancellor_bury_public_first: function( notif )
        {
          let card2 = {};
          Object.assign(card2, {
            id: `${notif.args.player_id}-fake-2` //the 'lvt-card' bit is added in the getId function of the HandStock
          });

          this.deck.addCard(card2, {
            fromStock: this.opponentHands[notif.args.player_id],
          });
        },

        notif_chancellor_bury_public_second: function( notif )
        {
          let card = {};
          Object.assign(card, {
            id: `${notif.args.player_id}-fake-1` //the 'lvt-card' bit is added in the getId function of the HandStock
          });

          this.deck.addCard(card, {
            fromStock: this.opponentHands[notif.args.player_id],
          });
        },

        notif_chancellor_bury: function( notif )
        {
          const keptCardId = notif.args.card.id;
          const allCards = this.playerHand.getCards();

          allCards.forEach(card => {
          if (card.id != keptCardId) {
            const buriedCard = {
              id: card.id,
              type: null,
            };

            this.deck.addCard(buriedCard, {
                fromStock: this.playerHand,
                visible: false,
                index: this.deck.getCards().length,
                updateInformations: true
                });
            }
          this.deselect();
          });

          this.playerHand.unselectAll?.();
          document.querySelectorAll('.keep').forEach(el => dojo.removeClass(el, 'keep'));
          this.deselect();
        },

        notif_newCardPrivate: function( notif )
        {
          let card = {};
          Object.assign(card, {
            id: notif.args.card.id,
            type: notif.args.card.type,
          });

            this.playerHand.addCard(card, { fromStock: this.deck });
            this.playerHand.setCardVisible(card, true);
        },

        notif_newCardPublic: function( notif )
        {
          const fake0Id = `lvt-card-${notif.args.player_id}-fake-0`;
          fakeNumberToUse = document.getElementById(fake0Id) ? '1' : '0';

          let card = {
            id: `${notif.args.player_id}-fake-${fakeNumberToUse}` //the 'lvt-card' bit is added in the getId function of the HandStock
          };

          const opponentHand = this.opponentHands[notif.args.player_id];
            opponentHand.addCard(card, { fromStock: this.deck });
            opponentHand.setCardVisible(card, false);
        },

        notif_cardPlayed: function( notif )
        {
          this.showDiscussion(notif);
          this.updateUiForCardPlayed(notif.args.card.id, notif.args.card.type, notif.args.player_id, notif.args.card_type.value);
        },

        notif_reveal: function( notif )
        {
          let card = {};
          Object.assign(card, {
            id: `${notif.args.player_id}-fake-0`,
            type: notif.args.card_type,
          });

          opponentHand = this.opponentHands[notif.args.player_id];
          opponentHand.setCardVisible(card, true);
          
          if (notif.args.timeout)
          {
            setTimeout(() => {
              opponentHand.setCardVisible(card, false);
            }, 2000);
          }
        },

        notif_cardexchange: function( notif )
        {
          firstPlayer = Number(notif.args.player_1);
          secondPlayer = Number(notif.args.player_2);

            const [otherPlayerId, playerCardDetails, opponentCardDetails] =
                (firstPlayer === this.player_id)
                    ? [secondPlayer, notif.args.player_1_card, notif.args.player_2_card]
                    : [firstPlayer, notif.args.player_2_card, notif.args.player_1_card];

            opponentHand = this.opponentHands[otherPlayerId];

            playerCard = this.playerHand.getCards()[0];
            opponentCard = opponentHand.getCards()[0];

            this.playerHand.removeAll();
            opponentHand.removeAll();

            newPlayerCard = {
              id: opponentCardDetails.id,  
              type: opponentCardDetails.type
            }

            newOpponentCard = {
              id: `${otherPlayerId}-fake-0`,
            }

            this.playerHand.addCard(newPlayerCard, {
                fromStock: opponentHand,
                visible: true
            });

            opponentHand.addCard(newOpponentCard, {
                fromStock: this.playerHand,
                visible: false
            });
        },

        notif_cardexchange_opponents: function( notif )
        {
          firstPlayer = Number(notif.args.player_1);
          secondPlayer = Number(notif.args.player_2);

          if (firstPlayer === this.player_id
            || secondPlayer === this.player_id) {
            return;
          }
            
          //both opponents
          const stock1 = this.opponentHands[firstPlayer];
          const stock2 = this.opponentHands[secondPlayer];

          stock1.removeAll();
          stock2.removeAll();

          newPlayer1Card = {
            id: `lvt-card-${firstPlayer}-fake-0`, 
          }

          newPlayer2Card = {
            id: `lvt-card-${secondPlayer}-fake-0`, 
          }

          stock1.addCard(newPlayer1Card, {
            fromStock: stock2,
            visible: true
          });

          stock2.addCard(newPlayer2Card, {
            fromStock: stock1,
            visible: true
          });
        }
    });

  function getCardSpriteBackgroundPosition(card, cardConstants) {

    const CARD_SPRITE_MAP = {
        [cardConstants.GUARD]: { col: 0, row: 0 }, // Guard
        [cardConstants.PRIEST]: { col: 1, row: 0 }, // Priest
        [cardConstants.BARON]: { col: 2, row: 0 }, // Baron
        [cardConstants.HANDMAID]: { col: 3, row: 0 }, // Handmaid
        [cardConstants.PRINCE]: { col: 4, row: 0 }, // Prince
        [cardConstants.CHANCELLOR]: { col: 5, row: 0 }, // Chancellor
        [cardConstants.KING]: { col: 0, row: 1 }, // King
        [cardConstants.COUNTESS]: { col: 1, row: 1 }, // Countess
        [cardConstants.PRINCESS]: { col: 2, row: 1 }, // Princess
        [cardConstants.SPY]: { col: 3, row: 1 }, // Spy
        "back": { col: 4, row: 1 }, // Card Back
          "rules": { col: 5, row: 1 } // Rules Card
      };
        let mapping = CARD_SPRITE_MAP[card.type];
        if (!mapping) mapping = CARD_SPRITE_MAP["back"];
        const x = mapping.col * 100
        const y = mapping.row * 100
        return `-${x}% -${y}%`;
    }

    //TODO - can we make this also use percentages?
    function buildPlayedCardBadges(gamedatas) {
      const rootStyles = getComputedStyle(document.documentElement);
      const SPRITE_WIDTH_ORIGINAL = parseFloat(rootStyles.getPropertyValue('--badge-sprite-width'));
      const SPRITE_HEIGHT_ORIGINAL = parseFloat(rootStyles.getPropertyValue('--badge-sprite-height'));
      const BADGE_WIDTH = 36;
      const BADGE_HEIGHT = 36;

      const COLUMNS = 4;
      const ROWS = 6; // up to 24 slots

      const container = document.getElementById('lvt-badges-area');
      container.innerHTML = '';

      // Create the grid container
      const grid = document.createElement('div');
      grid.className = 'lvt-badge-grid';

      // Gather all badges in a flat array (sorted for consistent order)
      const badges = [];
      Object.values(gamedatas.card_types).forEach(cardInfo => {
          const value = cardInfo.value;
          const count = cardInfo.qt;
          for (let i = 0; i < count; i++) {
              badges.push(value);
          }
      });
      badges.sort((a, b) => a - b);

      // Add up to COLUMNS * ROWS badges, filling down each column
      for (let index = 0; index < badges.length && index < COLUMNS * ROWS; index++) {
          const value = badges[index];
          const badge = document.createElement('div');
          badge.className = 'lvt-card-badge';
          badge.id = `lvt-card-badge-${index+1}`;
          badge.setAttribute('data-type', value);
          badge.style.backgroundImage = `url(${g_gamethemeurl}img/cardnumbers.png)`;
          // Scale the full image to fit vertically
          badge.style.backgroundSize = `auto ${BADGE_HEIGHT}px`;
          // Scale the offset: (value * original width) * (displayed height / original height)
          const xOffset = -(value * SPRITE_WIDTH_ORIGINAL * (BADGE_HEIGHT / SPRITE_HEIGHT_ORIGINAL));
          badge.style.backgroundPosition = `${xOffset}px 0`;
          badge.style.width = BADGE_WIDTH + "px";
          badge.style.height = BADGE_HEIGHT + "px";
          grid.appendChild(badge);
      }

      container.appendChild(grid);

      markBadgesAsPlayed(gamedatas);
  }

  function markBadgesAsPlayed(gamedatas) {
    Object.values(gamedatas.discard).forEach(discardArray => {
      if (Array.isArray(discardArray)) {
        discardArray.forEach(card => {
          const value = gamedatas.card_types[card.type].value;
          markBadgeAsPlayed(value);
        });
      }
    });
  }

  function markBadgeAsPlayed(value) {
    const badges = Array.from(document.querySelectorAll(`.lvt-card-badge[data-type="${value}"]`));
    badges.reverse();
    if (badges && badges.length > 0) {
      for (let i = 0; i < badges.length; i++) {
        if (!badges[i].classList.contains('played')) {
          badges[i].classList.add('played');
          return badges[i];
        }
      }
    }
  }

});
