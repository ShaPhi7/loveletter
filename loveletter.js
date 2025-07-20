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

            this.cardHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--lvt-card-height'));
            this.cardWidth = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--lvt-card-width'));

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
                    <div id="lvt-playertables"></div>
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
              div.classList.add('card-container');
              div.style.position = 'relative';
              },
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

});
