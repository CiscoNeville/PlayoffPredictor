$(".explain").click(function(e) {
    var x = e.pageX - this.offsetLeft - 20;
    var y = e.pageY - this.offsetTop + 22;
    $(".tooltip").show().css({
        left: x,
        top: y
    }).delay(3000).fadeOut();
    return false;
});

$(".tooltip").click(function() {
    $(this).hide(); 
});


//alert('Hello, World!');

