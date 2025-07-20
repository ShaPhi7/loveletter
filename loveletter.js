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

            this.playerHand = new ebg.stock();
            this.deck = null;
            this.opponentHands = {};
            this.discards = {};
            this.discussionTimeout = {};

            this.selectedOpponentId = null;
            this.selectedCardId = null; 
            this.selectedCardType = null;

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

            this.cardsManager = new CardManager(this, {
              getId: (card) => `lvt-card-${card.id}`,
              
              cardHeight: this.cardHeight,
              cardWidth: this.cardWidth,
              
              setupDiv: (card, div) => {
              div.classList.add('lvt-card-container');
              div.style.position = 'relative';
              },
              
              setupFrontDiv: (card, div) => {
                    div.classList.add('lvt-card');
                    console.log("Setting up front div for card", card);
                        div.style.backgroundPosition = getCardSpriteBackgroundPosition(card, this.cardHeight, this.cardWidth, window.CARD_CONSTANTS);
                    div.id = `card-${card.id}-front`;
                },


            });

            Object.values(gamedatas.players).forEach((player) => {
                document.getElementById("lvt-player-tables").insertAdjacentHTML("beforeend", `
                    <div class="lvt-player-table" id="lvt-player-table-${player.id}">
                        <div class="lvt-player-table-name" id="lvt-player-table-name-${player.id} style="color:#${player.color};">${player.name}</div>
                        <div class="lvt-player-table-card" id="lvt-player-table-card-${player.id}"></div>
                    </div>
                `);
            });

            for (var player_id in gamedatas.players) {
              console.log("Setting up player table for player", player_id);
                new LineStock(this.cardsManager, document.getElementById('lvt-player-table-card-' + player_id), {});
              }

            this.deck = new LineStock(this.cardsManager, document.getElementById('lvt-deck-area'), {});

            gamedatas.deck.forEach((card) => {
              console.log("Adding card to deck", card);
                this.deck.addCard({
                    id: card.id,
                    type: card.type,
                    type_arg: card.type_arg
                });
            });

        },

        onSelectPlayer: function(event) {
            const playerId = event.currentTarget.id.replace('lvt-playertable-', '');

            Object.keys(this.lvtPlayers).forEach(pid => {
              dojo.removeClass('lvt-playertable-' + pid, 'selectedOpponent');
            });
            dojo.addClass('lvt-playertable-' + playerId, 'selectedOpponent');

            this.selectedOpponentId = playerId;

            this.tryPlaySelectedCard();
        },

        onPlayerHandSelectionChanged: function(control_name, item_id) {
            const items = this.playerHand.getSelectedItems();
            this.selectedCardId = items.length == 1 ? Number(items[0].id) : null;
            this.selectedCardType = items.length == 1 ? Number(items[0].type) : null;

            if (this.selectedCardId) {
              this.tryPlaySelectedCard();
            }
        },

        tryPlaySelectedCard: function()
        {
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
              && cardType !== this.PRINCE)
            {
              this.showMessage(_("You cannot target yourself with this card."), "error");
              return;
            }

            // Check out of the round
            if(dojo.hasClass('lvt-playertable-'+this.selectedOpponentId, 'outOfTheRound'))
            {
              this.showMessage( _("This player is out of the round"), 'error' );
              return;
            }   
        
            // Check protection
            if(dojo.style('lvt-playertable-'+this.selectedOpponentId, 'protected'))
            {
              this.showMessage( _("This player is protected and cannot be targeted by any card effect."), 'error' );
              return;
            }
          }
            this.playCard(this.selectedCardId, -1, this.selectedOpponentId);
        },

        playCard: function(card, guess_id, opponent_id)
        {
          this.ajaxcall( "/loveletter/loveletter/playCard.html", { 
                                                      lock: true, 
                                                      card: card,
                                                      guess: guess_id,
                                                      opponent: opponent_id
                                                    },    this, function( result ) {  }, function( is_error) { } );  
        },

    });

  function getCardSpriteBackgroundPosition(card, cardHeight, cardWidth, cardConstants) {

    console.log("Getting background position for card", card);

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
        const x = mapping.col * cardWidth;
        const y = mapping.row * cardHeight;
        return `-${x}px -${y}px`;
    }

});
