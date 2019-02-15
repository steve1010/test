// Codeausführung erst beginnen, wenn DOM bereit ist

var playerCards = "";
var dealerCards = "";

var morethanonePcard = false;
var morethanoneDcard = false;

const player = "player";
const dealer = "dealer";

var winDealer = 0;
var winPlayer = 0;
var newGame = true;

$(function() {
	
	$('#button_new_game').hide();
	// steps 1-3:
	setup();
	
	// add action listener to buttons
	$('#button_next_card').click(function(){requestNewCard(player);});
	$('#button_end_game').click(function(){$('#button_next_card').hide();$('#button_end_game').hide();morethanoneDcard = true;requestNewCard(dealer);});
	
	$('#button_new_game').click(function(){
		if (newGame) {
            newGame = false;
		    $('#button_new_game').hide();
			$('#player_cards').empty();
			$('#dealer_cards').empty();
			$('#player_value').text("0");
			$('#dealer_value').text("0");
			updateCards(player);
			updateCards(dealer);
			morethanoneDcard = false;
			morethanonePcard = false;
			setup();
			$('#button_next_card').show();
			$('#button_end_game').show();
		}
		
	});
});

function setup() {
	// step 1
    requestNewCard(player);
	
	// step 2
    requestNewCard(dealer);
    
    morethanonePcard = true;
	
	// step 3
    requestNewCard(player);
}

function updateCards(who) {
    if (who == "player") {
		playerCards = "";
        $("#player_cards img").each(function(){
			playerCards = playerCards.concat("\"" + $(this).attr("alt") + "\",");
		});
		playerCards = playerCards.slice(0, playerCards.length-1);
    } else if (who == "dealer") {
		dealerCards = "";
       	$("#dealer_cards img").each(function(){
			dealerCards = dealerCards.concat("\"" + $(this).attr("alt") + "\",");
		});
		dealerCards = dealerCards.slice(0, dealerCards.length-1);
    } else{
		console.log("Wrong usage: only 'dealer' or 'player' allowed.");
	}
}

function requestNewCard(cardFor) {
	
    var url = "php/blackjack_card.php?dealer_cards=[" + dealerCards + "]&player_cards=[" + playerCards + "]&card_for="+cardFor;
	$.ajax({
		url: url,
		async: false,
		type: 'GET',
		dataType: 'json',
		success: function (json) {
			$(json).each(function(index, value){
				processReturn(value, cardFor);
				});
		}
	});
}

function processReturn(json, cardFor) {
	
	processCards(json, cardFor);
	if (json.winner == "dealer") {
		winDealer++;
       $("#win_counter").text("Dealer " + winDealer + ":" + winPlayer + " Player");
	
    }else if (json.winner == "player") {
		winPlayer++;
		 $("#win_counter").text("Dealer " + winDealer + ":" + winPlayer + " Player");
	
	}
	
	if (json.game_state == "game-over") {
		alert(json.msg);
		$('#button_end_game').hide();
		$('#button_next_card').hide();
		newGame = true;
		$('#button_new_game').show();
    } else{
		if (morethanoneDcard) {
			requestNewCard(dealer);
		}        
    }
}

function processCards(json, cardFor) {
	var cards;
	var newCard = "";
	
	if (cardFor == player) {
		cards = json.player_cards;
		if (morethanonePcard) {
			newCard = cards[cards.length-1];
		} else {
			newCard = cards;
		}
		$("#player_value").text(json.player_value);
	
		showPlayerCard(newCard);
    } else {
		cards = json.dealer_cards;
		if (morethanoneDcard) {
			newCard = cards[cards.length-1];
		} else {
			newCard = cards;
		}
		
		$("#dealer_value").text(json.dealer_value);
		
		showDealerCard(newCard);
	}
	updateCards(cardFor);
}

function showPlayerCard(card_id) {
	var card = $('<img />', { 
		src: 'png/' + card_id + '.png',
		alt: card_id
	});
	card.appendTo('#player_cards');
}
		
function showDealerCard(card_id) {
	var card = $('<img />', { 
		src: 'png/' + card_id + '.png',
		alt: card_id
	});
	card.appendTo('#dealer_cards');
}