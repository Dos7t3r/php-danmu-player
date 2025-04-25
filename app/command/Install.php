<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class Install extends Command
{
   protected function configure()
   {
       // 指令配置
       $this->setName('install')
           ->setDescription('Install OldBili Player system');
   }

   protected function execute(Input $input, Output $output)
   {
       $output->writeln('OldBili Player 安装程序');
       $output->writeln('=======================');
       
       // 检查数据库连接
       $output->writeln('正在检查数据库连接...');
       try {
           Db::execute('SELECT 1');
           $output->writeln('数据库连接成功！');
       } catch (\Exception $e) {
           $output->error('数据库连接失败: ' . $e->getMessage());
           $output->writeln('请检查 .env 文件中的数据库配置');
           return 1;
       }
       
       // 检查是否已安装
       $output->writeln('检查是否已安装...');
       try {
           $tables = Db::query('SHOW TABLES LIKE "obp_%"');
           if (!empty($tables)) {
               $confirm = $this->output->confirm($input, '检测到已存在OBP相关表，是否重新安装？（将清空所有数据）', false);
               if (!$confirm) {
                   $output->writeln('安装已取消');
                   return 0;
               }
               
               // 删除现有表
               $output->writeln('正在删除现有表...');
               foreach ($tables as $table) {
                   $tableName = current($table);
                   Db::execute("DROP TABLE IF EXISTS `{$tableName}`");
               }
           }
       } catch (\Exception $e) {
           $output->error('检查表失败: ' . $e->getMessage());
           return 1;
       }
       
       // 执行安装SQL
       $output->writeln('正在创建数据库表...');
       try {
           $sqlFile = file_get_contents(root_path() . 'install.sql');
           $sqlStatements = explode(';', $sqlFile);
           
           foreach ($sqlStatements as $sql) {
               $sql = trim($sql);
               if (!empty($sql)) {
                   Db::execute($sql);
               }
           }
           
           $output->writeln('数据库表创建成功！');
       } catch (\Exception $e) {
           $output->error('创建数据库表失败: ' . $e->getMessage());
           return 1;
       }
       
       // 创建必要目录
       $output->writeln('正在创建必要目录...');
       $dirs = [
           'public/danmaku',
           'public/uploads',
           'runtime/log',
           'runtime/cache',
           'runtime/temp'
       ];
       
       foreach ($dirs as $dir) {
           $path = root_path() . $dir;
           if (!is_dir($path)) {
               if (mkdir($path, 0755, true)) {
                   $output->writeln("创建目录成功: {$dir}");
               } else {
                   $output->warning("创建目录失败: {$dir}，请手动创建并设置权限");
               }
           } else if (!is_writable($path)) {
               $output->warning("目录已存在但不可写: {$dir}，请设置正确的权限");
           } else {
               $output->writeln("目录检查通过: {$dir}");
           }
       }
       
       // 安装完成
       $output->writeln('');
       $output->writeln('OldBili Player 安装完成！');
       $output->writeln('默认管理员账号: admin');
       $output->writeln('默认管理员密码: admin123');
       $output->writeln('请立即登录后台修改默认密码！');
       $output->writeln('后台地址: http://您的域名/admin');
       
       return 0;
   }
}
