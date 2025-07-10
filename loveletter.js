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
    "ebg/stock"
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
        },

        setup: function(gamedatas) {
            console.log("Love Letter setup", gamedatas);
            
            dojo.place('lvt-table-center', 'lvt-playertables');

            this.lvtPlayers = {};
            const playerIds = Object.keys(gamedatas.players);
            const totalPlayers = playerIds.length;

            // Rotate so local player is first
            const rotatedPlayerIds = [...playerIds];
            while (rotatedPlayerIds[0] !== String(this.player_id)) {
                rotatedPlayerIds.push(rotatedPlayerIds.shift());
            }

            const radius = 300;

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
            console.log(gamedatas.hand)
            // Ensure gamedatas.hand is iterable
            for( var type_id in gamedatas.card_types ) {
              this.playerHand.addItemType( type_id, 0, g_gamethemeurl+'img/cards.jpg', type_id-1 );
            }

            for( var i in this.gamedatas.hand )
            {
                var card = this.gamedatas.hand[i];
                this.playerHand.addToStockWithId( card.type, card.id );
            }

            rotatedPlayerIds.forEach((player_id) => {
              const player = gamedatas.players[player_id]; //TODO - make this opponents only
              if (!player.eliminated) {
                const handDiv = document.createElement('div');
                handDiv.className = 'lvt-hand';
                handDiv.id = `lvt-hand-${player_id}`;
                this.lvtPlayers[player_id].node.appendChild(handDiv);

                // Add a card to the hand
                const cardDiv = document.createElement('div');
                cardDiv.className = 'lvt-card lvt-card-back';
                handDiv.appendChild(cardDiv);
              }
            });

            stackDeckCards();
            //TODO - put in proper place, here for testing only.
            // Add shuffle animation
            shuffleDeckAnimation().then(() => {
                console.log('Deck shuffled!');
            });
        },

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            dojo.subscribe( 'newCard', this, "notif_newCard" );
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
    });

function shuffleDeckAnimation({
  containerId = 'lvt-table-center',
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

function stackDeckCards(containerId = 'lvt-table-center', offsetX = 1, offsetY = 1) {
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

});
