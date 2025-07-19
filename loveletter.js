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

            this.animationManager = new AnimationManager(this, { duration: 700 });

            Object.assign(this, window.CARD_CONSTANTS);
        },

        setup: function(gamedatas) {
            console.log("Love Letter setup", gamedatas);

            dojo.place('lvt-center-area', 'lvt-playertables');
            console.log(gamedatas);
            this.lvtPlayers = {};
            const playerIds = Object.keys(gamedatas.players);
            const totalPlayers = playerIds.length;

            // Rotate so local player is first
            const rotatedPlayerIds = [...playerIds];
            while (rotatedPlayerIds[0] !== String(this.player_id)) {
                rotatedPlayerIds.push(rotatedPlayerIds.shift());
            }

            const radius = 350;

            rotatedPlayerIds.forEach((player_id, index) => {
                const player = gamedatas.players[player_id];
                const angle = (2 * Math.PI * index) / totalPlayers;

                const x = Math.cos(angle) * radius;
                const y = Math.sin(angle) * radius;

                const html = `
                    <div id="lvt-playertable-${player_id}" class="lvt-playertable"
                         style="left: calc(50% + ${x}px); top: calc(50% + ${y}px); transform: translate(-50%, -50%)">
                        <div id="lvt-player-name-${player_id}" class="lvt-player-name" style="color:#${player.color}">${player.name}</div>
                        <div id="lvt-player-card-${player_id}" class="lvt-player-card"></div>
                    </div>`;

                dojo.place(html, "lvt-playertables");

                this.lvtPlayers[player_id] = {
                    id: player_id,
                    node: document.getElementById(`lvt-playertable-${player_id}`)
                };
            });

            const rootStyles = getComputedStyle(document.documentElement);
            const cardWidth    = parseFloat(rootStyles.getPropertyValue('--card-width'));
            const cardHeight   = parseFloat(rootStyles.getPropertyValue('--card-height'));
            // Get cardScale from the .hand element if present, otherwise fallback to root
            const spriteCols   = parseFloat(rootStyles.getPropertyValue('--sprite-cols'));
            const spriteRows   = parseFloat(rootStyles.getPropertyValue('--sprite-rows'));
            const cardScale    = parseFloat(rootStyles.getPropertyValue('--hand-card-scale'));

            this.playerHand.create(this, $('lvt-player-card-' + this.player_id), cardWidth * cardScale * spriteCols, cardHeight * cardScale * spriteRows);
            this.playerHand.selectable = 1;
            this.playerHand.autowidth = true;
            this.playerHand.resizeItems(cardWidth * cardScale, cardHeight * cardScale, cardWidth * spriteCols * cardScale, cardHeight * spriteRows * cardScale);
            this.playerHand.apparenceBorderWidth = '3px';
            this.playerHand.image_items_per_row = spriteCols;
            dojo.connect( this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );

            // Ensure gamedatas.hand is iterable
            for( var type_id in gamedatas.card_types ) {
              this.playerHand.addItemType(type_id, 0, g_gamethemeurl+'img/cards.jpg', type_id-21);
            }

            for( var i in this.gamedatas.hand )
            {
                var card = this.gamedatas.hand[i];
                console.log(gamedatas.hand[i]);
                console.log(card.type, card.id);
                this.playerHand.addToStockWithId(card.type, card.id);
            }

            rotatedPlayerIds.forEach((player_id) => {
              const player = gamedatas.players[player_id];
                if (!player.eliminated && player_id != this.player_id) {
                const opponentHand = new ebg.stock();
                const handDiv = document.createElement('div');
                handDiv.className = 'lvt-hand';
                handDiv.id = `lvt-hand-${player_id}`;
                this.lvtPlayers[player_id].node.appendChild(handDiv);
                // Make the player table clickable and trigger onSelectPlayer
                console.log(this.lvtPlayers[player_id].node);
                dojo.connect(this.lvtPlayers[player_id].node, 'onclick', this, 'onSelectPlayer');
                this.lvtPlayers[player_id].node.style.cursor = 'pointer';

                opponentHand.create(this, handDiv, cardWidth * cardScale * spriteCols, cardHeight * cardScale * spriteRows);
                opponentHand.autowidth = true;
                opponentHand.resizeItems(cardWidth * cardScale, cardHeight * cardScale, cardWidth * spriteCols * cardScale, cardHeight * spriteRows * cardScale);
                opponentHand.image_items_per_row = spriteCols;
                opponentHand.addItemType('hand-card-back', 0, g_gamethemeurl + 'img/cards.jpg', 10); // -1 for back sprite
                opponentHand.selectable = 0;
                
                for (let i = 0; i < gamedatas.cardcount.hand[player_id]; i++) {
                  opponentHand.addToStockWithId('hand-card-back', `back_${player_id}_${i}`);
                }

                this.opponentHands[player_id] = opponentHand;
                }
            });

            stackDeckCards();

            buildPlayedCardBadges(gamedatas);
            
            this.setupNotifications();
            //TODO - put in proper place, here for testing only.
            // Add shuffle animation
            shuffleDeckAnimation().then(() => {
                console.log('Deck shuffled!');
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

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            //dojo.subscribe( 'newCard', this, "notif_newCard" );
            dojo.subscribe( 'cardPlayedLong', this, "notif_cardPlayed" );
            dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            this.notifqueue.setSynchronous( 'cardPlayedLong', 3000 );
        },

        notif_newCard: function( notif ) 
        {
            console.log( 'notif_newCard', notif );

            if( notif.args.from )
            {
                this.playerHand.addToStockWithId( notif.args.card.type, notif.args.card.id, 'lvt-playertable-' + notif.args.from );            
            }
            else
            {        
                this.playerHand.addToStockWithId( notif.args.card.type, notif.args.card.id, 'deck' );            
            }
            
            if( notif.args.remove )
            {
                this.playerHand.removeFromStockById( notif.args.remove.id );
            }
        },

        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed', notif );

            if(this.player_id == notif.args.player_id) {
            this.playerHand.removeFromStockById(notif.args.card.id);
            }

            const badgeValue = this.gamedatas.card_types[notif.args.card.type].value;

            animateRealCardPlay({
                cardId: notif.args.card.id,
                cardType: notif.args.card.type,
                badgeValue,
                thisId: String(this.player_id),
                activeId: notif.args.player_id,
                opponentId: notif.args.opponent_id,
                animationManager: this.animationManager
            });
            
/*            if( typeof notif.args.noeffect == 'undefined' )
            {
                if( notif.args.card.type == this.GUARD )
                {
                  // Guard : who are you?
                  this.showDiscussion( notif.args.player_id, dojo.string.substitute( _('${player_name}, I think you are a ${guess}!'), { 
                    player_name: '<span style="color:#'+this.gamedatas.players[ notif.args.opponent_id ].color+'">'+ this.gamedatas.players[ notif.args.opponent_id ].name+'</span>',
                    guess: '<b>'+notif.args.guess_name+'</b>'
                  } ) );
                }
                else if( notif.args.card.type == this.PRIEST)
                {
                  // Priest : show your card

                  var delay = 0;
                  for( var i in notif.args.opponents )
                  {
                    var opponent_id = notif.args.opponents[i];
                    this.showDiscussion( notif.args.player_id, dojo.string.substitute( _('${player_name} please show me your card.'), { player_name: '<span style="color:#'+this.gamedatas.players[ opponent_id ].color+'">'+ this.gamedatas.players[ opponent_id ].name+'</span>' } ), delay );
                    this.showDiscussion( opponent_id, _('Here it is.'), delay+2000 );
                    
                    delay += 2000;
                  }
                }
                else if( notif.args.card.type == this.BARON || notif.args.card.type == 11 )
                {
                  this.showDiscussion( notif.args.player_id, dojo.string.substitute( _('${player_name}, let`s compare our cards...'), { player_name: '<span style="color:#'+this.gamedatas.players[ notif.args.opponent_id ].color+'">'+ this.gamedatas.players[ notif.args.opponent_id ].name+'</span>' } ) );
                  this.showDiscussion( notif.args.opponent_id, _('Alright.'), 2000 );
                }
                else if( notif.args.card.type == this.HANDMAID )
                {
                  this.showDiscussion( notif.args.player_id, _("I'm protected for one turn.") );
                }
                else if( notif.args.card.type == this.PRINCE )
                {
                  if( notif.args.player_id != notif.args.opponent_id )
                  {
                    this.showDiscussion( notif.args.player_id, dojo.string.substitute( _('${player_name}, you must discard your card.'), { player_name: '<span style="color:#'+this.gamedatas.players[ notif.args.opponent_id ].color+'">'+ this.gamedatas.players[ notif.args.opponent_id ].name+'</span>' } ) );
                    this.showDiscussion( notif.args.opponent_id, _('Alright.'), 2000 );
                  }
                  else
                  {
                    this.showDiscussion( notif.args.player_id, _('I play the Prince effect against myself and discard my card.') );
                  }
                }
                else if( notif.args.card.type == this.KING  )
                {
                  this.showDiscussion( notif.args.player_id, dojo.string.substitute( _('${player_name}, we must exchange our hand.'), { player_name: '<span style="color:#'+this.gamedatas.players[ notif.args.opponent_id ].color+'">'+ this.gamedatas.players[ notif.args.opponent_id ].name+'</span>' } ) );
                  this.showDiscussion( notif.args.opponent_id, _('Alright.'), 2000 );
                }
            } */           
        },
    });

function shuffleDeckAnimation({
  containerId = 'lvt-deck-area',
  cardClass = 'lvt-card-back',
  spreadRadius = 80,
  hopRadius = 60,
  duration = 200,
  delayStep = 45,
} = {}) {
  return new Promise((resolve) => {
    const container = document.getElementById(containerId);
    if (!container) {
      console.warn('Deck container not found:', containerId);
      resolve(false);
      return;
    }

    const elements = [];

    elements.push(...Array.from(container.getElementsByClassName(cardClass)));
    elements.reverse();

    elements.forEach((el, i) => {
      const angle = Math.random() * 2 * Math.PI;
      const dist = spreadRadius + Math.random() * 20;
      const x1 = Math.cos(angle) * dist;
      const y1 = Math.sin(angle) * dist;

      setTimeout(() => {
        el.style.transition = `transform ${duration}ms ease-out`;
        el.style.transform = `translate(${x1}px, ${y1}px)`;

        setTimeout(() => {
          const hopAngle = Math.random() * 2 * Math.PI;
          const x2 = Math.cos(hopAngle) * hopRadius;
          const y2 = Math.sin(hopAngle) * hopRadius;

          el.style.transition = `transform ${duration}ms ease-in-out`;
          el.style.transform = `translate(${x1 + x2}px, ${y1 + y2}px)`;

          setTimeout(() => {
            el.style.transition = `transform ${duration}ms ease-in`;
            el.style.transform = `translate(0px, 0px)`;

          }, duration);
        }, duration);
      }, i * delayStep);
    });
  });
}

function buildPlayedCardBadges(gamedatas) {
const BADGE_WIDTH_ORIGINAL = 127;  // original badge width in the sprite
const BADGE_WIDTH = 36;            // new displayed badge width
const BADGE_HEIGHT = 36;
const SPRITE_HEIGHT_ORIGINAL = 127; // (if the sprite image is exactly square)

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
        const xOffset = -(value * BADGE_WIDTH_ORIGINAL * (BADGE_HEIGHT / SPRITE_HEIGHT_ORIGINAL));
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

function stackDeckCards(containerId = 'lvt-deck-area', offsetX = 1, offsetY = 1) {
  const TOTAL_CARDS = 21;

  // Remove all existing deck cards
  const container = document.getElementById(containerId);
  container.querySelectorAll('.lvt-card-back').forEach(card => card.remove());

  // Count cards in hands and discards
  const handCards = document.querySelectorAll('.lvt-hand .lvt-card, .lvt-discard .lvt-card');
  const toRemove = handCards.length;

  // Calculate how many cards should be in the deck
  const deckCount = TOTAL_CARDS - toRemove;

  // Create the correct number of deck cards
  for (let i = 0; i < deckCount; i++) {
    const cardDiv = document.createElement('div');
    cardDiv.className = 'lvt-card-back';
    cardDiv.id = `deck_${i+1}`;
    container.appendChild(cardDiv);
  }

  // Stack the deck cards visually
  const cards = Array.from(container.querySelectorAll('.lvt-card-back'));
  cards.forEach((card, i) => {
    card.style.right = `${i * offsetY}px`;
    card.style.bottom = `${i * offsetX}px`;
    card.style.zIndex = i;
  });
  console.log('Stacked deck cards in', containerId, 'Count:', cards.length);
}

async function animateRealCardPlay({
    cardId,
    cardType,    // Not directly used but available for future effects
    badgeValue,
    thisId,      // String
    activeId,    // String or Number
    opponentId   // String or Number
}) {
    let handCard = null;
    if (String(thisId) == String(activeId)) {
        handCard = document.getElementById(`lvt-player-card-${thisId}_item_${cardId}`);
    } else {
        const opponentHandDiv = document.getElementById(`lvt-hand-${activeId}`);
        if (opponentHandDiv) {
            handCard = opponentHandDiv.querySelector(`[id^="lvt-hand-${activeId}_item_back_${activeId}_1"]`);
        }
    }
    if (!handCard) {
        console.warn('Could not find hand card element for animation');
        return;
    }

    const badge = markBadgeAsPlayed(badgeValue);
    if (!badge) {
        console.warn('Could not find badge to animate to');
        return;
    }

    // 1. Get card's starting position and move to body
    const fromRect = handCard.getBoundingClientRect();
    document.body.appendChild(handCard);
    Object.assign(handCard.style, {
        position: 'fixed',
        left: `${fromRect.left}px`,
        top: `${fromRect.top}px`,
        width: `${fromRect.width}px`,
        height: `${fromRect.height}px`,
        margin: '0',
        zIndex: 9999,
        pointerEvents: 'none',
        transition: ''
    });

    // Helper to do slide/fade sequence after flip (or immediately if no flip)
    function doSlideAndFade() {
        // Step 1: Move to opponent if needed
        if (opponentId && String(opponentId) !== String(thisId)) {
            const opponentTable = document.getElementById(`lvt-playertable-${opponentId}`);
            if (opponentTable) {
                const oppRect = opponentTable.getBoundingClientRect();
                setTimeout(() => {
                    handCard.style.transition = 'left 0.5s cubic-bezier(.5,1.4,.6,1), top 0.5s cubic-bezier(.5,1.4,.6,1)';
                    handCard.style.left = `${oppRect.left}px`;
                    handCard.style.top = `${oppRect.top}px`;

                    // Step 2: After move to opponent, move to badge
                    setTimeout(() => {
                        const badgeRect = badge.getBoundingClientRect();
                        handCard.style.transition = 'left 0.6s cubic-bezier(.7,1.6,.7,1), top 0.6s cubic-bezier(.7,1.6,.7,1)';
                        handCard.style.left = `${badgeRect.left}px`;
                        handCard.style.top = `${badgeRect.top}px`;

                        // Step 3: After move to badge, fade and remove
                        setTimeout(() => {
                            handCard.style.transition = 'opacity 0.4s';
                            handCard.style.opacity = '0';
                            setTimeout(() => handCard.remove(), 400);
                        }, 650); // after move to badge finishes
                    }, 550); // after move to opponent finishes
                }, 20);

                return;
            }
        }

        // If no opponent, slide directly to badge then fade
        setTimeout(() => {
            const badgeRect = badge.getBoundingClientRect();
            handCard.style.transition = 'left 0.6s cubic-bezier(.7,1.6,.7,1), top 0.6s cubic-bezier(.7,1.6,.7,1)';
            handCard.style.left = `${badgeRect.left}px`;
            handCard.style.top = `${badgeRect.top}px`;

            setTimeout(() => {
                handCard.style.transition = 'opacity 0.4s';
                handCard.style.opacity = '0';
                setTimeout(() => handCard.remove(), 400);
            }, 650); // after slide to badge finishes
        }, 20);
    }

    // If opponent's card (so needs flip)
    if (String(thisId) !== String(activeId)) {
        // CSS FLIP ANIMATION (simple version)
        handCard.style.transition = 'transform 0.4s';
        handCard.style.transform = 'rotateY(180deg)';
        setTimeout(() => {
            handCard.classList.remove('lvt-card-back');
            handCard.classList.add('lvt-card-front');
            handCard.style.transition = '';
            handCard.style.transform = '';
            doSlideAndFade();
        }, 400);
    } else {
        // Active player—already face up
        doSlideAndFade();
    }
}





});
