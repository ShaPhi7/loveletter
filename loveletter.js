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

            const rootStyles = getComputedStyle(document.documentElement);
            this.cardWidth    = parseFloat(rootStyles.getPropertyValue('--card-width'));
            this.cardHeight   = parseFloat(rootStyles.getPropertyValue('--card-height'));
            this.deckScale    = parseFloat(rootStyles.getPropertyValue('--deck-scale'));
            this.handScale    = parseFloat(rootStyles.getPropertyValue('--hand-scale'));

            Object.assign(this, window.CARD_CONSTANTS);
        },

        setup: function(gamedatas) {
            console.log("Love Letter setup", gamedatas);

            this.getGameAreaElement().insertAdjacentHTML("beforeend", `
                <div id="lvt-background"></div>
            `);

            this.getGameAreaElement().insertAdjacentHTML("beforeend", `
                <div id="lvt-play-area">
                    <div id="lvt-center-area">
                        <div id="lvt-deck-area"></div>
                        <div id="lvt-badges-area"></div>
                    </div>
                    <div id="lvt-player-tables"></div>
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
              div.style.position = 'relative';
              },
              
              setupFrontDiv: (card, div) => {
                    div.classList.add('lvt-card');
                        div.style.backgroundPosition = getCardSpriteBackgroundPosition(card, this.cardHeight, this.cardWidth, this.deckScale, window.CARD_CONSTANTS);
                    div.id = `card-${card.id}-front`;
                },

              setupBackDiv: (card, div) => {
                  div.classList.add('lvt-card');
                      div.style.backgroundPosition = getCardSpriteBackgroundPosition("back", this.cardHeight, this.cardWidth, this.deckScale, window.CARD_CONSTANTS);
                  div.id = `card-${card.id}-back`;
                },
            });

              this.handManager = new CardManager(this, {
              getId: (card) => `lvt-card-${card.id}`,
              
              cardHeight: this.cardHeight,
              cardWidth: this.cardWidth,
              
              setupDiv: (card, div) => {
              div.classList.add('lvt-card-container');
              div.style.position = 'relative';
              },
              
              setupFrontDiv: (card, div) => {
                    div.classList.add('lvt-card');
                        div.style.backgroundPosition = getCardSpriteBackgroundPosition(card, this.cardHeight, this.cardWidth, this.handScale, window.CARD_CONSTANTS);
                    div.id = `card-${card.id}-front`;
                },

              setupBackDiv: (card, div) => {
                  div.classList.add('lvt-card');
                      div.style.backgroundPosition = getCardSpriteBackgroundPosition("back", this.cardHeight, this.cardWidth, this.handScale, window.CARD_CONSTANTS);
                  div.id = `card-${card.id}-back`;
                },
            });

            _this = this;
            Object.values(gamedatas.players).forEach((player) => {
                document.getElementById("lvt-player-tables").insertAdjacentHTML("beforeend", `
                    <div class="lvt-player-table" id="lvt-player-table-${player.id}">
                        <div class="lvt-player-table-name" id="lvt-player-table-name-${player.id}" style="color:#${player.color};">${player.name}</div>
                        <div class="lvt-player-table-card" id="lvt-player-table-card-${player.id}"></div>
                    </div>
                `);
                
                const playerTable = document.getElementById(`lvt-player-table-${player.id}`);
                playerTable.addEventListener('click', function(event) {
                  
                  const tablePlayerId = this.id.replace('lvt-player-table-', '');
                  if (tablePlayerId === String(_this.player_id)) {
                    // Check if click was in your own card area
                    const cardArea = document.getElementById('lvt-player-table-card-' + tablePlayerId);
                    if (cardArea.contains(event.target) && event.target !== cardArea) {
                      return;
                    }
                  }

                  if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    _this.selectedOpponentId = null;
                    return;
                  }

                  const playerTables = Array.from(document.querySelectorAll('.lvt-player-table'));
                  playerTables.forEach(pt => pt.classList.remove('selected'));
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
                if (selectedCards.length > 0) {
                  this.onPlayerHandSelectionChanged(selectedCards[0]);
                }
              };

              const handValues = Object.values(gamedatas.hand);
              handValues.forEach(card => {
                console.log("Adding card to player table", card);
                this.playerHand.addCard(card);
                this.playerHand.setCardVisible(card, true);
              });

              const opponentHand = gamedatas.cardcount.hand[this.player_id];
              console.log("Setting up player table for opponent with hand", opponentHand);

              Object.values(gamedatas.players).forEach(player => {
                if (player.id != this.player_id) {
                  const opponentLine = new LineStock(this.handManager, document.getElementById('lvt-player-table-card-' + player.id), {});
                  opponentLine.setSelectionMode('single');
                  const opponentHandSize = gamedatas.cardcount.hand[player.id];
                    for (let i = 0; i < opponentHandSize; i++) {
                      const fakeCard = {
                        id: `${player.id}-fake-${i}`,
                      };
                      opponentLine.addCard(fakeCard);
                      opponentLine.setCardVisible(fakeCard, false);
                    }
                  this.opponentHands[player.id] = opponentLine;
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

            this.discard = new Deck(this.deckManager, document.getElementById('lvt-badges-area'), {});
            
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

        onSelectPlayer: function(playerId) {
          this.selectedOpponentId = playerId;
            this.playCardOrShowMessage();
        },

        onPlayerHandSelectionChanged: function(card) {
          this.selectedCardId = card ? Number(card.id) : null;
          this.selectedCardType = card ? Number(card.type) : null;
          this.playCardOrShowMessage();
        },

        playCardOrShowMessage: function()
        {     
          if (this.gamedatas.gamestate.active_player != this.player_id) {
            this.showMessage(_("It is not your turn."), "error");
            return;
          }

          if (!this.selectedCardId) {
            this.showMessage(_("Please select a card to play."), "info");
            return;
          }

          const cardType = this.selectedCardType;
          const requiresOpponent = [this.GUARD, this.PRIEST, this.BARON, this.PRINCE, this.KING].includes(cardType);
          if (!requiresOpponent)
          {
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
              return;
            }

            // Check out of the round
            if(dojo.hasClass('lvt-player-table-'+this.selectedOpponentId, 'outOfTheRound'))
            {
              this.showMessage( _("This player is out of the round"), 'error' );
              return;
            }   
        
            // Check protection
            if(dojo.style('lvt-player-table-'+this.selectedOpponentId, 'protected'))
            {
              this.showMessage( _("This player is protected and cannot be targeted by any card effect."), 'error' );
              return;
            }
          }
          if (this.selectedCardType === this.GUARD
            || this.selectedCardType === this.PRINCESS)
          {
            this.showConfirmationDialog();
          }  
          else
          {
            this.playCard(this.selectedCardId, -1, this.selectedOpponentId);
          }
        },

        showConfirmationDialog: function() {
         if (this.selectedCardType === this.PRINCESS) {
          this.confirmationDialog(_("Playing the Princess will knock you out of the round. Are you sure?"),
          ( () => {  
            this.playCard(this.selectedCardId, -1, this.selectedOpponentId);
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
            } );
            
            dojo.query( '.guardchoicelink' ).connect( 'onclick', this, function( evt ) {
              debugger;
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
        },

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            dojo.subscribe( 'newCard', this, "notif_newCard" );
            dojo.subscribe( 'cardPlayedLong', this, "notif_cardPlayed" );
            dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            //this.notifqueue.setSynchronous( 'cardPlayedLong', 3000 );

            dojo.subscribe( 'reveal', this, 'notif_reveal' );
            dojo.subscribe( 'reveal_long', this, 'notif_reveal' );
            this.notifqueue.setSynchronous( 'reveal', 2000 );
            this.notifqueue.setSynchronous( 'reveal_long', 3000 );

            dojo.subscribe( 'cardexchange', this, 'notif_cardexchange' );
            dojo.subscribe( 'cardexchange_opponents', this, 'notif_cardexchange_opponents' );
        },

        notif_newCard: function( notif )
        {
          let card = {};
          Object.assign(card, {
            id: notif.args.card.id,
            type: notif.args.card.type,
          });

          //TODO - do you need to add the player_id to the notification?
          //if( this.player_id == notif.args.player_id )
          //{
            this.playerHand.addCard(card, { fromStock: this.deck });
            this.playerHand.setCardVisible(card, true);
          //}
          //else
          //{
            //TODO - test
            // Add a fake card to the opponent's hand
            // const opponentHand = this.opponentHands[notif.args.player_id];
            // opponentHand.addCard(card, { fromStock: this.deck });
            // opponentHand.setCardVisible(card, false);
          //}
        },

        notif_cardPlayed: function( notif )
        {
          let card = {};
          Object.assign(card, {
          id: notif.args.card.id,
          type: notif.args.card.type,
          });

          if( this.player_id == notif.args.player_id )
          {
            
            this.discard.addCard(card, { fromStock: this.playerHand });
            const cardElement = this.handManager.getCardElement(card);
            cardElement.classList.add('fade-out');
            const allDescendants = cardElement.querySelectorAll('*');
            for (const descendant of allDescendants) {
              descendant.classList.add('fade-out');
            }
          }
          else
          {
            const opponentHand = this.opponentHands[notif.args.player_id];
            this.discard.addCard(card, {
                fromStock: opponentHand,
                updateInformations: {
                    id: card.id,
                    type: card.type,
                },
                visible: true
            });
          }
          setTimeout(() => {
            this.discard.removeCard(card);
            markBadgeAsPlayed(notif.args.card_type.value);
          }, 2000);
        },

        notif_reveal: function( notif )
        {
          let card = {};
          Object.assign(card, {
            id: notif.args.player_id + '-fake-0',
            type: notif.args.card_type,
          });

          opponentHand = this.opponentHands[notif.args.player_id];
          opponentHand.setCardVisible(card, true);
          setTimeout(() => {
            opponentHand.setCardVisible(card, false);
          }, 2000);
        },

        notif_cardexchange: function( notif )
        {
          debugger;
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
            id: `${firstPlayer}-fake-0`, 
          }

          newPlayer2Card = {
            id: `${secondPlayer}-fake-0`, 
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

  function getCardSpriteBackgroundPosition(card, cardHeight, cardWidth, cardScale, cardConstants) {

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
        const x = mapping.col * cardWidth * cardScale;
        const y = mapping.row * cardHeight * cardScale;
        return `-${x}px -${y}px`;
    }

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
