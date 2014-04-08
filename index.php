<!DOCTYPE html>
<html>
    <head>
        <title>Execute &middot; SchedU</title>
        <meta name="description" content="Execute the SchedU script.">
        <meta name="author" content="SchedU">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="http://getschedu.com/res/ico/favicon.png">
        <style>
            .align-center {
                height: 300px;
                width: 300px;
                margin: auto;
                position: absolute;
                top: 0;
                bottom: 0;
                right: 0;
                left: 0;
                text-align: center;
                font-family: Helvetica, Arial, sans-serif;
            }
            button {
                color: white;
                padding: 10px;
                font-size: 18px;
                border: 1px solid transparent;
                cursor: pointer;
            }
            button:focus { outline: 0; }
            .success, .loading, .green { display: none; }
            .red { background-color: red; }
            .green { background-color: green; }
            h1 { font-size: 3em; }
            .success h1 {
                color: white;
                background-color: green;
            }
            @-webkit-keyframes load {
                from { max-width: 0; }
                to { max-width: 4em; }
            }
            .loading {
                position: absolute;
                left: 50%;
                margin-left: -1.9em;
                color: #ccc;
            }
            .loading:before {
                position: absolute;
                color: darkgreen;
                content: attr(data-content);
                -webkit-animation: load 3s linear infinite alternate;
                overflow: hidden;
                max-width: 4em;
            }
        </style>
    </head>
    <body>
        <div class="align-center">
            <img src="http://getschedu.com/res/img/logo.png" alt="">
            <br>
            <button class="execute">Execute the Script</button>
            <br>
            <div class="confirm">
            <button class="green">Are you sure?</button>
            <label>
                Debug
                <input type="checkbox" name="debug">
            </label>
            <label>
                Send To Me
                <input type="checkbox" name="sendToMe">
            </label>
            <br>
            <h1 class="loading" data-content="Loading">Loading</h1>
            <br>
            <div class="success">
                <h1>Success</h1>
                <p></p>
            </div>
        </div>
        <script src="http://getschedu.com/res/js/jquery-1.9.0.min.js"></script>
        <script>
            $('.red').click(function () {
                $('.red').css('background-color', 'lightcoral');
                $('.green').show();
            });
            $('.green').click(function () {
                $('.loading').show();
                $.ajax({
                    type: 'POST',
                    url: 'http://requestb.in/19lbxi31',
                    data: { 
                        'variables[debug]': $(input[name='debug']).prop('checked'), 
                        'variables[sendToMe]': $(input[name='sendToMe']).prop('checked')
                    },
                    success: function(msg){
                        $('.loading').hide();
                        $('.success>p').html(data);
                        $('.success').show();
                    }
                });
            });
        </script>
    </body>
</html>
