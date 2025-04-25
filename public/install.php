<?php
/**
* OldBili Player v2.0.0 - 安装程序
* 
* 此文件用于在浏览器中安装OldBili Player系统
*/

// 开启错误显示（仅用于安装过程）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');
define('ROOT_PATH', __DIR__ . '/../');

// 检查是否已安装
if (file_exists(ROOT_PATH . 'runtime/install.lock')) {
  die('OldBili Player已经安装，如需重新安装，请删除runtime/install.lock文件');
}

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.2.5', '<')) {
  die('PHP版本必须 >= 7.2.5，当前版本: ' . PHP_VERSION);
}

// 检查必要的PHP扩展
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
  if (!extension_loaded($ext)) {
      $missingExtensions[] = $ext;
  }
}
if (!empty($missingExtensions)) {
  die('缺少必要的PHP扩展: ' . implode(', ', $missingExtensions));
}

// 检查.env文件
if (!file_exists(ROOT_PATH . '.env')) {
  if (file_exists(ROOT_PATH . '.env.example')) {
      die('请将.env.example复制为.env并配置数据库连接信息');
  } else {
      die('缺少.env配置文件');
  }
}

// 加载框架引导文件
require ROOT_PATH . 'vendor/autoload.php';

// 检查并创建必要的目录
$requiredDirs = [
   ROOT_PATH . 'runtime',
   ROOT_PATH . 'runtime/log',
   ROOT_PATH . 'runtime/cache',
   ROOT_PATH . 'runtime/temp',
   __DIR__ . '/uploads',
   __DIR__ . '/danmaku',
];

$dirStatus = [];
foreach ($requiredDirs as $dir) {
   if (!is_dir($dir)) {
       $created = @mkdir($dir, 0755, true);
       $dirStatus[$dir] = [
           'exists' => false,
           'created' => $created,
           'writable' => $created && is_writable($dir)
       ];
   } else {
       $dirStatus[$dir] = [
           'exists' => true,
           'created' => true,
           'writable' => is_writable($dir)
       ];
   }
}

// 执行安装
if (isset($_POST['install'])) {
   try {
       // 检查目录权限
       $dirErrors = [];
       foreach ($dirStatus as $dir => $status) {
           if (!$status['writable']) {
               $dirErrors[] = "目录 {$dir} 不可写，请设置正确的权限";
           }
       }
       
       if (!empty($dirErrors)) {
           throw new Exception(implode("<br>", $dirErrors));
       }
       
       // 手动执行SQL安装
       $sqlFile = file_get_contents(ROOT_PATH . 'install.sql');
       if (!$sqlFile) {
           throw new Exception("无法读取安装SQL文件");
       }
       
       // 连接数据库
       $envContent = file_get_contents(ROOT_PATH . '.env');
       preg_match('/HOSTNAME\s*=\s*(.+)/i', $envContent, $hostMatches);
       preg_match('/DATABASE\s*=\s*(.+)/i', $envContent, $dbMatches);
       preg_match('/USERNAME\s*=\s*(.+)/i', $envContent, $userMatches);
       preg_match('/PASSWORD\s*=\s*(.+)/i', $envContent, $passMatches);
       preg_match('/HOSTPORT\s*=\s*(.+)/i', $envContent, $portMatches);
       preg_match('/PREFIX\s*=\s*(.+)/i', $envContent, $prefixMatches);
       
       $dbHost = isset($hostMatches[1]) ? trim($hostMatches[1]) : 'localhost';
       $dbName = isset($dbMatches[1]) ? trim($dbMatches[1]) : '';
       $dbUser = isset($userMatches[1]) ? trim($userMatches[1]) : '';
       $dbPass = isset($passMatches[1]) ? trim($passMatches[1]) : '';
       $dbPort = isset($portMatches[1]) ? trim($portMatches[1]) : '3306';
       $dbPrefix = isset($prefixMatches[1]) ? trim($prefixMatches[1]) : 'obp_';
       
       $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
       $pdo = new PDO($dsn, $dbUser, $dbPass);
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       
       // 检查是否已存在表
       $tables = $pdo->query("SHOW TABLES LIKE '{$dbPrefix}%'")->fetchAll(PDO::FETCH_COLUMN);
       if (!empty($tables)) {
           // 删除现有表
           foreach ($tables as $table) {
               $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
           }
       }
       
       // 执行SQL语句
       $sqlStatements = explode(';', $sqlFile);
       foreach ($sqlStatements as $sql) {
           $sql = trim($sql);
           if (!empty($sql)) {
               $pdo->exec($sql);
           }
       }
       
       // 创建安装锁定文件
       file_put_contents(ROOT_PATH . 'runtime/install.lock', date('Y-m-d H:i:s'));
       
       // 安装成功
       $installSuccess = true;
   } catch (Exception $e) {
       $installError = $e->getMessage();
   }
}

// 检查数据库连接
$dbConnectionOk = false;
$dbErrorMessage = '';
try {
   $envContent = file_get_contents(ROOT_PATH . '.env');
   preg_match('/TYPE\s*=\s*(.+)/i', $envContent, $typeMatches);
   preg_match('/HOSTNAME\s*=\s*(.+)/i', $envContent, $hostMatches);
   preg_match('/DATABASE\s*=\s*(.+)/i', $envContent, $dbMatches);
   preg_match('/USERNAME\s*=\s*(.+)/i', $envContent, $userMatches);
   preg_match('/PASSWORD\s*=\s*(.+)/i', $envContent, $passMatches);
   preg_match('/HOSTPORT\s*=\s*(.+)/i', $envContent, $portMatches);
   
   $dbType = isset($typeMatches[1]) ? trim($typeMatches[1]) : 'mysql';
   $dbHost = isset($hostMatches[1]) ? trim($hostMatches[1]) : 'localhost';
   $dbName = isset($dbMatches[1]) ? trim($dbMatches[1]) : '';
   $dbUser = isset($userMatches[1]) ? trim($userMatches[1]) : '';
   $dbPass = isset($passMatches[1]) ? trim($passMatches[1]) : '';
   $dbPort = isset($portMatches[1]) ? trim($portMatches[1]) : '3306';
   
   if (!empty($dbHost) && !empty($dbName) && !empty($dbUser)) {
       try {
           $dsn = "{$dbType}:host={$dbHost};port={$dbPort};dbname={$dbName}";
           $pdo = new PDO($dsn, $dbUser, $dbPass);
           $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           $dbConnectionOk = true;
       } catch (PDOException $e) {
           $dbErrorMessage = $e->getMessage();
       }
   }
} catch (Exception $e) {
   $dbErrorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OldBili Player v2.0.0 安装程序</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
      body {
          background-color: #f8f9fa;
          padding-top: 40px;
          padding-bottom: 40px;
      }
      .install-container {
          max-width: 800px;
          margin: 0 auto;
          background: white;
          border-radius: 10px;
          box-shadow: 0 0 20px rgba(0,0,0,0.1);
          padding: 30px;
      }
      .logo {
          text-align: center;
          margin-bottom: 30px;
      }
      .logo h1 {
          color: #6c5ce7;
          font-weight: bold;
      }
      .step {
          margin-bottom: 20px;
          padding: 15px;
          border-radius: 5px;
          background-color: #f8f9fa;
      }
      .step-header {
          display: flex;
          align-items: center;
          margin-bottom: 10px;
      }
      .step-number {
          width: 30px;
          height: 30px;
          background-color: #6c5ce7;
          color: white;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-right: 10px;
          font-weight: bold;
      }
      .step-title {
          font-weight: bold;
          font-size: 18px;
      }
      .success-icon {
          color: #2ecc71;
          font-size: 24px;
          margin-right: 10px;
      }
      .error-icon {
          color: #e74c3c;
          font-size: 24px;
          margin-right: 10px;
      }
      .dir-path {
          font-family: monospace;
          font-size: 0.9em;
      }
      .env-info {
          background-color: #f8f9fa;
          padding: 10px;
          border-radius: 5px;
          font-family: monospace;
          margin-top: 10px;
      }
      .install-btn {
          background-color: #6c5ce7;
          border-color: #6c5ce7;
      }
      .install-btn:hover {
          background-color: #5b4ecc;
          border-color: #5b4ecc;
      }
  </style>
</head>
<body>
  <div class="container install-container">
      <div class="logo">
          <h1>OldBili Player v2.0.0</h1>
          <p class="text-muted">基于ThinkPHP 6的现代化弹幕播放器</p>
      </div>
      
      <?php if (isset($installSuccess)): ?>
          <div class="alert alert-success">
              <h4><i class="bi bi-check-circle-fill"></i> 安装成功！</h4>
              <p>OldBili Player v2.0.0已成功安装。</p>
              <p>默认管理员账号: <strong>admin</strong></p>
              <p>默认管理员密码: <strong>admin123</strong></p>
              <p>请立即登录后台修改默认密码！</p>
              <div class="mt-4">
                  <a href="/" class="btn btn-primary">访问首页</a>
                  <a href="/admin" class="btn btn-success">进入管理后台</a>
              </div>
          </div>
      <?php elseif (isset($installError)): ?>
          <div class="alert alert-danger">
              <h4><i class="bi bi-exclamation-triangle-fill"></i> 安装失败</h4>
              <p>安装过程中发生错误：</p>
              <pre><?php echo $installError; ?></pre>
              <form method="post" class="mt-3">
                  <button type="submit" name="install" class="btn btn-primary">重试安装</button>
              </form>
          </div>
      <?php else: ?>
          <div class="step">
              <div class="step-header">
                  <div class="step-number">1</div>
                  <div class="step-title">环境检查</div>
              </div>
              <div class="step-content">
                  <ul class="list-group mb-3">
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                          PHP版本 >= 7.2.5
                          <?php if (version_compare(PHP_VERSION, '7.2.5', '>=')): ?>
                              <span class="badge bg-success rounded-pill">✓ <?php echo PHP_VERSION; ?></span>
                          <?php else: ?>
                              <span class="badge bg-danger rounded-pill">✗ <?php echo PHP_VERSION; ?></span>
                          <?php endif; ?>
                      </li>
                      <?php foreach ($requiredExtensions as $ext): ?>
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                              <?php echo $ext; ?> 扩展
                              <?php if (extension_loaded($ext)): ?>
                                  <span class="badge bg-success rounded-pill">✓ 已安装</span>
                              <?php else: ?>
                                  <span class="badge bg-danger rounded-pill">✗ 未安装</span>
                              <?php endif; ?>
                          </li>
                      <?php endforeach; ?>
                  </ul>
              </div>
          </div>
          
          <div class="step">
              <div class="step-header">
                  <div class="step-number">2</div>
                  <div class="step-title">目录权限检查</div>
              </div>
              <div class="step-content">
                  <div class="alert alert-info">
                      <i class="bi bi-info-circle"></i> 安装程序将自动尝试创建必要的目录
                  </div>
                  <ul class="list-group mb-3">
                      <?php foreach ($dirStatus as $dir => $status): ?>
                          <li class="list-group-item">
                              <div class="d-flex justify-content-between align-items-center">
                                  <span class="dir-path"><?php echo $dir; ?></span>
                                  <?php if ($status['writable']): ?>
                                      <span class="badge bg-success rounded-pill">✓ 可写</span>
                                  <?php else: ?>
                                      <span class="badge bg-danger rounded-pill">✗ 不可写</span>
                                  <?php endif; ?>
                              </div>
                              <div class="small text-muted mt-1">
                                  <?php if (!$status['exists'] && $status['created']): ?>
                                      <i class="bi bi-check-circle-fill text-success"></i> 目录已自动创建
                                  <?php elseif (!$status['exists'] && !$status['created']): ?>
                                      <i class="bi bi-exclamation-triangle-fill text-danger"></i> 目录创建失败，请手动创建并设置权限
                                  <?php elseif ($status['exists'] && !$status['writable']): ?>
                                      <i class="bi bi-exclamation-triangle-fill text-danger"></i> 目录存在但不可写，请设置权限为755或777
                                  <?php endif; ?>
                              </div>
                          </li>
                      <?php endforeach; ?>
                  </ul>
                  
                  <?php if (array_filter($dirStatus, function($status) { return !$status['writable']; })): ?>
                      <div class="alert alert-warning">
                          <h5><i class="bi bi-exclamation-triangle-fill"></i> 部分目录不可写</h5>
                          <p>请使用以下命令设置目录权限：</p>
                          <pre class="bg-dark text-light p-2 rounded">
<?php foreach ($dirStatus as $dir => $status): ?>
<?php if (!$status['writable']): ?>
mkdir -p <?php echo $dir; ?>

chmod 755 <?php echo $dir; ?>

<?php endif; ?>
<?php endforeach; ?>
</pre>
                      </div>
                  <?php endif; ?>
              </div>
          </div>
          
          <div class="step">
              <div class="step-header">
                  <div class="step-number">3</div>
                  <div class="step-title">数据库配置检查</div>
              </div>
              <div class="step-content">
                  <?php if ($dbConnectionOk): ?>
                      <div class="alert alert-success">
                          <i class="bi bi-check-circle-fill"></i> 数据库连接成功
                      </div>
                      <div class="env-info">
                          <div>数据库类型: <?php echo htmlspecialchars($dbType); ?></div>
                          <div>数据库主机: <?php echo htmlspecialchars($dbHost); ?></div>
                          <div>数据库名称: <?php echo htmlspecialchars($dbName); ?></div>
                          <div>数据库用户: <?php echo htmlspecialchars($dbUser); ?></div>
                          <div>数据库端口: <?php echo htmlspecialchars($dbPort); ?></div>
                      </div>
                  <?php else: ?>
                      <div class="alert alert-danger">
                          <i class="bi bi-x-circle-fill"></i> 数据库连接失败
                          <?php if ($dbErrorMessage): ?>
                              <div class="mt-2">错误信息: <?php echo htmlspecialchars($dbErrorMessage); ?></div>
                          <?php endif; ?>
                      </div>
                      <p>请检查 .env 文件中的数据库配置是否正确：</p>
                      <div class="env-info">
                          <div>数据库类型: <?php echo htmlspecialchars($dbType); ?></div>
                          <div>数据库主机: <?php echo htmlspecialchars($dbHost); ?></div>
                          <div>数据库名称: <?php echo htmlspecialchars($dbName); ?></div>
                          <div>数据库用户: <?php echo htmlspecialchars($dbUser); ?></div>
                          <div>数据库端口: <?php echo htmlspecialchars($dbPort); ?></div>
                      </div>
                  <?php endif; ?>
              </div>
          </div>
          
          <div class="step">
              <div class="step-header">
                  <div class="step-number">4</div>
                  <div class="step-title">开始安装</div>
              </div>
              <div class="step-content">
                  <?php if (!$dbConnectionOk): ?>
                      <div class="alert alert-warning">
                          <i class="bi bi-exclamation-triangle-fill"></i> 数据库连接失败，请先修复数据库配置问题
                      </div>
                  <?php elseif (array_filter($dirStatus, function($status) { return !$status['writable']; })): ?>
                      <div class="alert alert-warning">
                          <i class="bi bi-exclamation-triangle-fill"></i> 部分目录不可写，请先修复目录权限问题
                      </div>
                  <?php else: ?>
                      <p>所有检查已通过，点击下面的按钮开始安装OldBili Player v2.0.0</p>
                      <form method="post">
                          <button type="submit" name="install" class="btn btn-primary install-btn">立即安装</button>
                      </form>
                  <?php endif; ?>
              </div>
          </div>
      <?php endif; ?>
      
      <div class="text-center mt-4 text-muted">
          <p>OldBili Player v2.0.0 &copy; 2023-2024</p>
      </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
