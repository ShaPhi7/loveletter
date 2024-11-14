{OVERALL_GAME_HEADER}


<div id="ll_background" class="{PLAYERS_NBR} {WITH_EXPANSION}">

    <div id="playertables">
    
        <div id="explanation_card">
            <div id="explanation_card_content">
                {EXPLANATION_CARD_CONTENT}
            </div>
        </div>
        <div id="explanation_card2">
            <div id="explanation_card_content2">
                {EXPLANATION_CARD_CONTENT2}
            </div>
        </div>

	    <!-- BEGIN player -->
        <div id="playertable_{PLAYER_ID}" class="playertable selectable_playertable whiteblock playertable_{DIR}">
            <div id="playertablename_{PLAYER_ID}" class="playertablename" style="color:#{PLAYER_COLOR}">
                {PLAYER_NAME} <i id="player_protection_{PLAYER_ID}" class="fa fa-shield player_protection"></i>
                <i id="player_jester_{PLAYER_ID}" class="fa fa-heart player_jester"></i>
                <i id="player_sycophant_{PLAYER_ID}" class="fa fa-bullseye player_sycophant"></i>
                <div id="discussion_bubble_{PLAYER_ID}" class="discussion_bubble"></div>
            </div>
            <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
            </div>
            <div class="discardcontent" id="discardcontent_{PLAYER_ID}">
            </div>
            <div id="playertable_cover_{PLAYER_ID}" class="playertable_cover"></div>
        </div>
        <!-- END player -->
	
	    <div id="tablecenter" class="playertable whiteblock playertable_center">

            <div class="playertablename">
                <span id="discard_name">{LABEL_LAST_PLAYED}</span>
                <span id="deck_name">{LABEL_DECK} (×<span id="deck_count">16</span>)</span>
            </div>


            <div id="discard" class="playertablecard">
            </div>
            <div id="deck" class="playertablecard">
                <div id="deck_1" class="deckcard">
                <div id="deck_2" class="deckcard">
                <div id="deck_3" class="deckcard">
                <div id="deck_4" class="deckcard">
                <div id="deck_5" class="deckcard">
                <div id="deck_6" class="deckcard">
                <div id="deck_7" class="deckcard">
                <div id="deck_8" class="deckcard">
                <div id="deck_9" class="deckcard">
                <div id="deck_10" class="deckcard">
                <div id="deck_11" class="deckcard">
                <div id="deck_12" class="deckcard">
                <div id="deck_13" class="deckcard">
                <div id="deck_14" class="deckcard">
                <div id="deck_15" class="deckcard">
                <div id="deck_16" class="deckcard">
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
                </div>
            </div>

	    </div>
	
    </div>

</div>


<script type="text/javascript">

var jstpl_cardontable = '<div id="cardontable_${id}" class="cardontable" style="background-position:-${x}px -0px">\
                        </div>';

var jstpl_card_content = '<div class="cardcontent cardtype_${type}" id="cardcontent_${id}">\
                            <div class="cardtitle">${name}</div>\
                         </div>';

</script>  

{OVERALL_GAME_FOOTER}
