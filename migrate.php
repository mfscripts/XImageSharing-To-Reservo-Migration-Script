<?php
/*
 * XImageSharing => Reservo Migration Script.
 * 
 * Mirgration script for converting users, images data
 * from XImageSharing. Set the config values below, upload this
 * script to the base of your Reservo install and load it within
 * a browser.
 * 
 * REQUIREMENTS:
 * MySQL PDO
 * Reservo installed
 */

// XImageSharing - database settings
define('XIMAGESHARING_DB_HOST', 'localhost');
define('XIMAGESHARING_DB_NAME', '');
define('XIMAGESHARING_DB_USER', '');
define('XIMAGESHARING_DB_PASS', '');

// XImageSharing password hash. Needed to decode user passwords
// for migration. This value is in XFileConfig.pm in password_salt.
// i.e. pasword_salt  => '',
define('XIMAGESHARING_PASSWORD_HASH', '[hash]');

// XImageSharing storage path for local files. Include trailing forward slash.
//define('XIMAGESHARING_FILE_STORAGE_PATH', 'cgi-bin/uploads/00000/');

// XImageSharing payments currency in three digital country code
define('XIMAGESHARING_PAYMENT_CURRENCY', 'USD');

// XImageSharing grouping for file storage. This value is in XFileConfig.pm in files_per_folder. Normally 5000. Set to 0 to avoid this migration appending the folder names to the original file. i.e. 00001, 00002, 00003 etc.
define('XIMAGESHARING_FOLDER_GROUPING', '5000');

// Reservo - config file path
define('RESERVO_CONFIG_FILE_PATH', '_config.inc.php');

/*
 * ******************************************************************
 * END OF CONFIG SECTION, YOU SHOULDN'T NEED TO CHANGE ANYTHING ELSE
 * ******************************************************************
 */
 
// allow up to 24 hours for it to run
set_time_limit(60*60*24);

// make sure we are in the root and can find the config file
if (!file_exists(RESERVO_CONFIG_FILE_PATH))
{
    die('ERROR: Could not load Reservo config file. Ensure you\'re running this script from the root of your Reservo install.');
}

// include Reservo config
require_once(RESERVO_CONFIG_FILE_PATH);

// test database connectivity, Reservo
try
{
    $ysDBH = new PDO("mysql:host=" . _CONFIG_DB_HOST . ";dbname=" . _CONFIG_DB_NAME, _CONFIG_DB_USER, _CONFIG_DB_PASS);
    $ysDBH->exec("set names utf8");
}
catch (PDOException $e)
{
    die('ERROR: Could not connect to Reservo database. ' . $e->getMessage());
}

// test database connectivity, XImageSharing
try
{
    $xisDBH = new PDO("mysql:host=" . XIMAGESHARING_DB_HOST . ";dbname=" . XIMAGESHARING_DB_NAME, XIMAGESHARING_DB_USER, XIMAGESHARING_DB_PASS);
    $xisDBH->exec("set names utf8");
}
catch (PDOException $e)
{
    die('ERROR: Could not connect to XImageSharing database. ' . $e->getMessage());
}

// check file storage exists
/*
if(!file_exists(XIMAGESHARING_FILE_STORAGE_PATH))
{
    die('ERROR: Could not find XImageSharing file storage folder. ' . XIMAGESHARING_FILE_STORAGE_PATH);
}
*/

// check Reservo file storage is writable
if(!is_writable(_CONFIG_FILE_STORAGE_PATH))
{
    die('ERROR: Reservo file storage is not writable. Set to CHMOD 777 or 755. ' . _CONFIG_FILE_STORAGE_PATH);
}

// initial checks passed, load stats for converting and get user confirmation
$xfsStats = array();

// servers
$getFiles               = $xisDBH->query('SELECT COUNT(srv_id) AS total FROM Servers');
$row                    = $getFiles->fetchObject();
$xfsStats['totalServers'] = (int) $row->total;

// files
$getFiles               = $xisDBH->query('SELECT COUNT(file_id) AS total FROM Files');
$row                    = $getFiles->fetchObject();
$xfsStats['totalFiles'] = (int) $row->total;

// users
$getUsers               = $xisDBH->query('SELECT COUNT(usr_id) AS total FROM Users');
$row                    = $getUsers->fetchObject();
$xfsStats['totalUsers'] = (int) $row->total;

// folders
$getFolders               = $xisDBH->query('SELECT COUNT(fld_id) AS total FROM Folders');
$row                      = $getFolders->fetchObject();
$xfsStats['totalFolders'] = (int) $row->total;

// payments
$getPayments               = $xisDBH->query('SELECT COUNT(id) AS total FROM Payments');
$row                       = $getPayments->fetchObject();
$xfsStats['totalPayments'] = (int) $row->total;

// page setup
define('PAGE_TITLE', 'XImageSharing => Reservo Migration Tool');
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title><?php echo PAGE_TITLE; ?></title>
        <meta name="distribution" content="global" />
        <style>
            body
            {
                margin: 0px;
                padding: 0;
                font: 100%/1.0 helvetica, arial, sans-serif;
                color: #444;
                background: #ccc;
            }

            h1, h2, h3, h4, h5, h6
            {
                margin: 0 0 1em;
                line-height: 1.1;
            }

            h2, h3 { color: #003d5d; }
            h2 { font-size: 218.75%; }
            h3 { font-size: 137.5%; }
            h4 { font-size: 118.75%; }
            h5 { font-size: 112.5%; }
            p { margin: 0 0 1em; }
            img { border: none; }
            a:link { color: #035389; }
            a:visited { color: #09619C; }

            a:focus
            {
                color: #fff;
                background: #000;
            }

            a:hover { color: #000; }

            a:active
            {
                color: #cc0000;
                background: #fff;
            }

            table
            {
                margin: 1em 0;
                border-collapse: collapse;
                width: 100%;
            }

            table caption
            {
                text-align: left;
                font-weight: bold;
                padding: 0 0 5px;
                text-transform: uppercase;
                color: #236271;
            }

            table td, table th
            {
                text-align: left;
                border: 1px solid #b1d2e4;
                padding: 5px 10px;
                vertical-align: top;
            }

            table th { background: #ecf7fd; }

            blockquote
            {
                background: #ecf7fd;
                margin: 1em 0;
                padding: 1.5em;
            }

            code
            {
                background: #ecf7fd;
                font: 115% courier, monaco, monospace;
                margin: 0 .3em;
            }

            abbr, acronym
            {
                border-bottom: .1em dotted;
                cursor: help;
            }
            #container
            {
                margin: 0 0px;
                background: #fff;
            }

            #header
            {
                background: #ccc;
                padding: 20px;
            }

            #header h1 { margin: 0; }

            #navigation
            {
                float: left;
                width: 100%;
                background: #333;
            }

            #navigation ul
            {
                margin: 0;
                padding: 0;
            }

            #navigation ul li
            {
                list-style-type: none;
                display: inline;
            }

            #navigation li a
            {
                display: block;
                float: left;
                padding: 5px 10px;
                color: #fff;
                text-decoration: none;
                border-right: 1px solid #fff;
            }

            #navigation li a:hover { background: #383; }

            #content
            {
                clear: left;
                padding: 20px;
            }

            #content h2
            {
                color: #000;
                font-size: 160%;
                margin: 0 0 .5em;
            }

            #footer
            {
                background: #ccc;
                text-align: right;
                padding: 20px;
                height: 1%;
                font-size: 12px;
            }

            .important, .error
            {
                color: red;
                font-weight: bold;
            }

            .success
            {
                color: green;
                font-weight: bold;
            }

            .button
            {
                border:1px solid #4b546a;-webkit-box-shadow: #B7B8B8 0px 1px 0px inset;-moz-box-shadow: #B7B8B8 0px 1px 0px inset; box-shadow: #B7B8B8 0px 1px 0px inset;-webkit-border-radius: br_rightpx br_leftpx -1px -1px;-moz-border-radius: br_rightpx br_leftpx -1px -1px;border-radius: br_rightpx br_leftpx -1px -1px; padding: 10px 10px 10px 10px; text-decoration:none; display:inline-block;text-shadow: -1px -1px 0 rgba(0,0,0,0.3);font-weight:bold; color: #FFFFFF;
                background-color: #606c88; background-image: -webkit-gradient(linear, left top, left bottom, from(#606c88), to(#3f4c6b));
                background-image: -webkit-linear-gradient(top, #606c88, #3f4c6b);
                background-image: -moz-linear-gradient(top, #606c88, #3f4c6b);
                background-image: -ms-linear-gradient(top, #606c88, #3f4c6b);
                background-image: -o-linear-gradient(top, #606c88, #3f4c6b);
                background-image: linear-gradient(to bottom, #606c88, #3f4c6b);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=#606c88, endColorstr=#3f4c6b);
                cursor: pointer;
            }

            .button:hover
            {
                border:1px solid #4b546a;
                background-color: #4b546a; background-image: -webkit-gradient(linear, left top, left bottom, from(#4b546a), to(#2c354b));
                background-image: -webkit-linear-gradient(top, #4b546a, #2c354b);
                background-image: -moz-linear-gradient(top, #4b546a, #2c354b);
                background-image: -ms-linear-gradient(top, #4b546a, #2c354b);
                background-image: -o-linear-gradient(top, #4b546a, #2c354b);
                background-image: linear-gradient(to bottom, #4b546a, #2c354b);filter:progid:DXImageTransform.Microsoft.gradient(GradientType=0,startColorstr=#4b546a, endColorstr=#2c354b);
            }
        </style>
    </head>
    <body>
        <div id="container">
            <div id="header">
                <h1>
                    <?php echo PAGE_TITLE; ?>
                </h1>
            </div>
            <div id="content">
                <?php if (!isset($_REQUEST['submitted'])): ?>
                    <h2>
                        Confirm Migration
                    </h2>
                    <p>
                        Use this tool to migrate your user, files, folders and payments data from XImageSharing Pro into a Reservo install.
                    </p>
                    <p>
                        To start, upload this file to the root of your Reservo install, ensure you've set your configuration at the top of this php script, then click 'start migration' below. To confirm, we've loaded your existing XImageSharing table sizes below.
                    </p>
                    <p style='padding-top: 4px; padding-bottom: 4px;'>
                        <table style='width: auto;'>
                            <tr>
                                <th style='width: 150px;'>XIS Table:</th>
                                <th style='width: 150px;'>Total Rows:</th>
                            </tr>
							<tr>
                                <td>Servers:</td>
                                <td><?php echo $xfsStats['totalServers']; ?></td>
                            </tr>
                            <tr>
                                <td>Files:</td>
                                <td><?php echo $xfsStats['totalFiles']; ?></td>
                            </tr>
                            <tr>
                                <td>Users:</td>
                                <td><?php echo $xfsStats['totalUsers']; ?></td>
                            </tr>
                            <tr>
                                <td>Folders:</td>
                                <td><?php echo $xfsStats['totalFolders']; ?></td>
                            </tr>
                            <tr>
                                <td>Payments: (paid)</td>
                                <td><?php echo $xfsStats['totalPayments']; ?></td>
                            </tr>
                        </table>
                    </p>
                    <p class="important">
                        IMPORTANT: When you start this process, any existing data in your Reservo database will be cleared. Please ensure you've backed up both databases beforehand so you can easily revert if you need to.
                    </p>
					<p class="important">
                        This process wont actually migrate your files, it converts all the data in your XImageSharing database for Reservo. Although it does keep the same file names for your stored files. So after this is completed you should move all your files into the Reservo /files/ folder on each server.
                    </p>
                    <p style="padding-top: 4px;">
                        <form method="POST" action="migrate.php">
                            <input type="hidden" name="submitted" value="1"/>
                            <input type="submit" value="Start Migration" name="submit" class="button" onClick="return confirm('Are you sure you want to delete all the data from your Reservo database and import from the XImageSharing database?');"/>
                        </form>
                    </p>
                <?php else: ?>
                    <h2>
                        Importing Data
                    </h2>
                    <p>
                        Clearing existing Reservo data... 
                        <?php
                        // delete reservo data
                        $ysDBH->query('DELETE FROM download_tracker');
                        $ysDBH->query('DELETE FROM file');
                        $ysDBH->query('DELETE FROM file_folder');
                        $ysDBH->query('DELETE FROM payment_log');
                        $ysDBH->query('DELETE FROM sessions');
                        $ysDBH->query('DELETE FROM session_transfer');
                        $ysDBH->query('DELETE FROM stats');
                        $ysDBH->query('DELETE FROM users');
						$ysDBH->query('DELETE FROM file_server');

                        echo 'done.';
                        ?>
                        <?php updateScreen(); ?>
                    </p>
                    <p style='padding-top: 4px; padding-bottom: 4px;'>
                        <table style='width: auto;'>
                            <tr>
                                <th style='width: 150px;'>XIS Table:</th>
                                <th style='width: 150px;'>Total Rows:</th>
                                <th style='width: 150px;'>Reservo Table:</th>
                                <th style='width: 150px;'>Successful Rows:</th>
                                <th style='width: 150px;'>Failed Rows:</th>
                            </tr>
							
							<?php
                            // do servers
                            $getServers = $xisDBH->query('SELECT srv_id, srv_name, srv_ip, srv_htdocs_url, srv_status, srv_created, srv_disk, srv_disk_max FROM Servers');
                            $success     = 0;
                            $error       = 0;
                            while($row = $getServers->fetch())
                            {
								$statusId = 2;
								if($row['srv_status'] == 'READONLY')
								{
									$statusId = 3;
								}
								
								$domain = $row['srv_htdocs_url'];
								$domainArr = parse_url($domain);
								$domain = $domainArr['host'];
								
                                // insert into Reservo db
                                $sql   = "INSERT INTO file_server (id, serverLabel, serverType, ipAddress, statusId, storagePath, fileServerDomainName, scriptPath, totalSpaceUsed, maximumStorageBytes) VALUES (:id, :serverLabel, 'direct', :ipAddress, :statusId, 'files/', :fileServerDomainName, '/', :totalSpaceUsed, :maximumStorageBytes)";
                                $q     = $ysDBH->prepare($sql);
                                $count = $q->execute(array(
                                    ':id'            => $row['srv_id'],
                                    ':serverLabel'   => $row['srv_name'],
                                    ':ipAddress'  => $row['srv_ip'],
                                    ':statusId'        => $statusId,
                                    ':fileServerDomainName' => $domain,
									':totalSpaceUsed' => $row['srv_disk'],
									':maximumStorageBytes' => $row['srv_disk_max'],
                                ));

                                if ($count)
                                {
                                    $success++;
                                }
                                else
                                {
                                    $error++;
                                }
                            }
                            ?>
                            <tr>
                                <td>Servers:</td>
                                <td><?php echo $xfsStats['totalServers']; ?></td>
                                <td>file_server:</td>
                                <td><?php echo $success; ?></td>
                                <td><?php echo $error; ?></td>
                            </tr>
                            <?php updateScreen(); ?>
							
							
                            <?php
                            // do files
                            $getFiles       = $xisDBH->query('SELECT file_id, usr_id, file_name, file_code, file_fld_id, file_views AS file_downloads, file_size, file_ip, file_last_view AS file_last_download, file_created, srv_id FROM Files');
                            $success        = 0;
                            $error          = 0;
                            $fileMoveErrors = array();
							while($row = $getFiles->fetch())
                            {
								$dbFileStorageValue = $row['file_code'];
								
								// apprend file path
								if((int)XIMAGESHARING_FOLDER_GROUPING > 0)
								{
									$actualFolderId = floor((int)$row['file_id']/5000);
									$formattedFolderId = str_pad($actualFolderId, 5, "0", STR_PAD_LEFT);
									$dbFileStorageValue = $formattedFolderId.'/'.$dbFileStorageValue;
								}

                                // insert into Reservo db
                                $sql   = "INSERT INTO file (id, originalFilename, shortUrl, fileType, extension, fileSize, localFilePath, userId, totalDownload, uploadedIP, uploadedDate, statusId, visits, lastAccessed, deleteHash, folderId, serverId, accessPassword) VALUES (:id, :originalFilename, :shortUrl, :fileType, :extension, :fileSize, :localFilePath, :userId, :totalDownload, :uploadedIP, :uploadedDate, :statusId, :visits, :lastAccessed, :deleteHash, :folderId, :serverId, :accessPassword)";
                                $q     = $ysDBH->prepare($sql);
                                $count = $q->execute(array(
                                    ':id'               => $row['file_id'],
                                    ':originalFilename' => $row['file_name'],
                                    ':shortUrl'         => $row['file_code'],
                                    ':fileType'         => guess_mime_type($row['file_name']),
                                    ':extension'        => strtolower(get_file_extension($row['file_name'])),
                                    ':fileSize'         => $row['file_size'],
                                    ':localFilePath'    => $dbFileStorageValue,
                                    ':userId'           => ((int)$row['usr_id']==0?'null':$row['usr_id']),
                                    ':totalDownload'    => $row['file_downloads'],
                                    ':uploadedIP'       => long2Ip32bit($row['file_ip']),
                                    ':uploadedDate'     => $row['file_created'],
                                    ':statusId'         => 1,
                                    ':visits'           => $row['file_downloads'],
                                    ':lastAccessed'     => $row['file_last_download'],
                                    ':deleteHash'       => MD5($row['file_id'].rand(10000,99999).$row['file_created']),
                                    ':folderId'         => $row['file_fld_id'],
                                    ':serverId'         => $row['srv_id'],
                                    ':accessPassword'   => null,
                                ));

                                if ($count)
                                {
                                    $success++;
                                }
                                else
                                {
                                    $error++;
                                }
                            }
                            ?>
                            <tr>
                                <td>Files:</td>
                                <td><?php echo $xfsStats['totalFiles']; ?></td>
                                <td>file:</td>
                                <td><?php echo $success; ?></td>
                                <td><?php echo $error; ?></td>
                            </tr>
                            <?php updateScreen(); ?>

                            <?php
                            // do users
                            $getUsers = $xisDBH->query('SELECT usr_id, usr_login, DECODE( usr_password, \'' . XIMAGESHARING_PASSWORD_HASH . '\' ) AS raw_password, usr_email, usr_adm, usr_status, usr_disk_space, usr_premium_expire, usr_created, usr_lastlogin, usr_lastip FROM Users ORDER BY usr_id DESC');
                            $success  = 0;
                            $error    = 0;
                            while($row = $getUsers->fetch())
                            {
                                // insert into Reservo db
                                $sql       = "INSERT INTO users (id, username, password, level_id, email, lastlogindate, lastloginip, status, storageLimitOverride, datecreated, createdip, paidExpiryDate, paymentTracker, identifier) VALUES (:id, :username, :password, :level_id, :email, :lastlogindate, :lastloginip, :status, :storageLimitOverride, :datecreated, :createdip, :paidExpiryDate, :paymentTracker, :identifier)";
                                $q         = $ysDBH->prepare($sql);
                                $userLevel = 1;
                                if ($row['usr_adm'] == 1)
                                {
                                    $userLevel = 20;
                                }
                                elseif (strtotime($row['usr_premium_expire']) > time())
                                {
                                    $userLevel = 2;
                                }

                                $status = 'active';
                                if ($row['usr_status'] == 'PENDING')
                                {
                                    $status = 'pending';
                                }
                                elseif ($row['usr_status'] == 'BANNED')
                                {
                                    $status = 'suspended';
                                }
								
								$disk_space = '';
								if ($row['usr_disk_space'] > 0)
								{
								    $disk_space = $row['usr_disk_space'] * 1048576;
								}

                                $count = $q->execute(array(
                                    ':id'             => $row['usr_id'],
                                    ':username'       => $row['usr_login'],
                                    ':password'       => MD5($row['raw_password']),
                                    ':level_id'       => $userLevel,
                                    ':email'          => $row['usr_email'],
                                    ':lastlogindate'  => $row['usr_lastlogin'],
                                    ':lastloginip'    => long2Ip32bit($row['usr_lastip']),
                                    ':status'         => $status,
									':storageLimitOverride' => $disk_space,
                                    ':datecreated'    => $row['usr_created'],
                                    ':createdip'      => long2Ip32bit($row['usr_lastip']),
                                    ':paidExpiryDate' => $row['usr_premium_expire'],
                                    ':paymentTracker' => MD5(microtime() . $row['usr_id']),
                                    ':identifier'     => MD5(microtime() . $row['usr_id'] . microtime()),
                                ));

								if($q->errorCode() == 0)
								{
                                    $success++;
                                }
                                else
                                {
									if($error < 100)
									{
										$errorLocal = $q->errorInfo();
										echo 'Skipped Row: '.$errorLocal[2]."<br/>";
									}
									if($error == 100)
									{
										echo "<strong>... [truncated insert errors to first 100]</strong><br/>";
									}
                                    $error++;
                                }
                            }
                            ?>
                            <tr>
                                <td>Users:</td>
                                <td><?php echo $xfsStats['totalUsers']; ?></td>
                                <td>users:</td>
                                <td><?php echo $success; ?></td>
                                <td><?php echo $error; ?></td>
                            </tr>
                            <?php updateScreen(); ?>

                            <?php
                            // do folders
                            $getFolders = $xisDBH->query('SELECT fld_id, usr_id, fld_name FROM Folders');
                            $success    = 0;
                            $error      = 0;
                            while($row = $getFolders->fetch())
                            {
                                // insert into Reservo db
                                $sql   = "INSERT INTO file_folder (id, userId, folderName, isPublic) VALUES (:id, :userId, :folderName, :isPublic)";
                                $q     = $ysDBH->prepare($sql);
                                $count = $q->execute(array(
                                    ':id'         => $row['fld_id'],
                                    ':userId'     => $row['usr_id'],
                                    ':folderName' => $row['fld_name'],
                                    ':isPublic'   => 0,
                                ));

                                if ($count)
                                {
                                    $success++;
                                }
                                else
                                {
                                    $error++;
                                }
                            }
                            ?>
                            <tr>
                                <td>Folders:</td>
                                <td><?php echo $xfsStats['totalFolders']; ?></td>
                                <td>file_folder:</td>
                                <td><?php echo $success; ?></td>
                                <td><?php echo $error; ?></td>
                            </tr>
                            <?php updateScreen(); ?>

                            <?php
                            // do payments
                            $getPayments = $xisDBH->query('SELECT id, usr_id, amount, status, created, pay_info AS pay_email, pay_type FROM Payments WHERE status=\'PAID\'');
                            $success     = 0;
                            $error       = 0;
                            while($row = $getPayments->fetch())
                            {
								// figure out status
								$newStatus = 'cancelled';
								switch($row['status'])
								{
									case 'PENDING':
										$newStatus = 'pending';
										break;
									case 'PAID':
										$newStatus = 'paid';
										break;
									// 'REJECTED':
									default:
										$newStatus = 'cancelled';
										break;
								}
								
								// insert withdraw request
								$sql   = "INSERT INTO plugin_reward_withdraw_request (id, reward_user_id, requested_date, amount, status, payment_date, payment_notes) VALUES (:id, :reward_user_id, :requested_date, :amount, :status, :payment_date, :payment_notes)";
                                $q     = $ysDBH->prepare($sql);
                                $count = $q->execute(array(
                                    ':id'            => $row['id'],
                                    ':reward_user_id'       => $row['reward_user_id'],
                                    ':requested_date'  => $row['created'],
                                    ':amount'        => $row['amount'],
									':status' => $newStatus,
									':payment_date' => $row['created'],
									':payment_notes' => $row['pay_type'].' '.$row['pay_email'],
                                ));

                                if ($count)
                                {
                                    $success++;
                                }
                                else
                                {
                                    $error++;
                                }
                            }
                            ?>
                            <tr>
                                <td>Payments: (paid)</td>
                                <td><?php echo $xfsStats['totalPayments']; ?></td>
                                <td>payment_log:</td>
                                <td><?php echo $success; ?></td>
                                <td><?php echo $error; ?></td>
                            </tr>
                            <?php updateScreen(); ?>

                        </table>
                    </p>

                    <?php
                    if (COUNT($fileMoveErrors) > 0)
                    {
                        echo '<p class="error">';
                        foreach ($fileMoveErrors AS $k => $fileMoveError)
                        {
                            // only show first 100
                            if ($k < 100)
                            {
                                echo '- ' . $fileMoveError . '<br/>';
                            }
                        }
                        echo '</p>';
                    }
                    ?>
                    <p>
                        Import finished. Note that your admin login to Reservo will be updated to reflect your old XImageSharing one.
                    </p>
                    <p style="padding-top: 4px;">
                        <form method="POST" action="migrate.php">
                            <input type="submit" value="Restart" name="submit" class="button"/>
                        </form>
                    </p>
                <?php endif; ?>
            </div>
            <div id="footer">
                Copyright &copy; <?php echo date('Y'); ?> <a href="https://reservo.co" target="_blank">Reservo.co</a>
            </div>
        </div>
    </body>
</html>

<?php

// local functions
function updateScreen()
{
    flush();
    ob_flush();
}

function get_file_extension($file_name)
{
    return substr(strrchr($file_name, '.'), 1);
}

function long2Ip32bit($ip)
{
    return long2ip((float) $ip);
}

function guess_mime_type($filename)
{
    $mime_types = array(
        'txt'  => 'text/plain',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'php'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'swf'  => 'application/x-shockwave-flash',
        'flv'  => 'video/x-flv',
        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // archives
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        'exe'  => 'application/x-msdownload',
        'msi'  => 'application/x-msdownload',
        'cab'  => 'application/vnd.ms-cab-compressed',
        // audio/video
        'mp3'  => 'audio/mpeg',
        'qt'   => 'video/quicktime',
        'mov'  => 'video/quicktime',
        // adobe
        'pdf'  => 'application/pdf',
        'psd'  => 'image/vnd.adobe.photoshop',
        'ai'   => 'application/postscript',
        'eps'  => 'application/postscript',
        'ps'   => 'application/postscript',
        // ms office
        'doc'  => 'application/msword',
        'rtf'  => 'application/rtf',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',
        // open office
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        'avi'  => 'video/avi',
    );

    $ext = strtolower(array_pop(explode('.', $filename)));
    if (array_key_exists($ext, $mime_types))
    {
        return $mime_types[$ext];
    }
    else
    {
        return 'application/octet-stream';
    }
}
?>