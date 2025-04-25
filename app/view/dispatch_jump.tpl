<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>跳转提示</title>
    <style type="text/css">
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #6c5ce7;
        }
        .message {
            font-size: 16px;
            margin-bottom: 30px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .jump {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        .link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c5ce7;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .link:hover {
            background-color: #5649c0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title"><?php echo $code == 1 ? '操作成功' : '操作失败'; ?></div>
        <div class="message <?php echo $code == 1 ? 'success' : 'error'; ?>">
            <?php echo(strip_tags($msg)); ?>
        </div>
        <p class="jump">页面将在 <span id="wait"><?php echo($wait); ?></span> 秒后自动跳转</p>
        <a href="<?php echo($url); ?>" class="link" id="href">立即跳转</a>
        <div class="footer">OldBili Player v2.0.0</div>
    </div>
    <script type="text/javascript">
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                }
            }, 1000);
        })();
    </script>
</body>
</html>
