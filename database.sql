/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `replication` (
  `uid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `orig_doi` varchar(200) NOT NULL,
  `orig_link_alternative` text NOT NULL,
  `orig_citation` text NOT NULL,
  `orig_abstract` text NOT NULL,
  `repl_author_last` varchar(255) NOT NULL,
  `repl_author_first` varchar(255) NOT NULL,
  `repl_year` int(4) UNSIGNED NOT NULL,
  `repl_type` enum('Reproduction','Replication','Reproduction & Replication') NOT NULL,
  `repl_level` enum('Bachelor thesis','Master thesis','Doctoral thesis','Class activity','Research project','other') NOT NULL DEFAULT 'Bachelor thesis',
  `repl_title` text NOT NULL,
  `repl_abstract` text NOT NULL,
  `result` enum('Successful! Original results replicated/reproduced successfully.','Mostly successful! Original and replicated/reproduced results match but only in effect direction.','Somewhat successful! Original and replicated/reproduced results match for some but not for other aspects.','Rather unsuccessful! Most original results did not replicate/reproduce successfully.','Unsuccessful! Original results did not replicate/reproduce whatsoever.','Not replicable/reproducible! Data and/or methods could not be employed in comparable fashion.','Success not determinable (elaborate!):') NOT NULL,
  `result_details` text NOT NULL,
  `repl_author_emails` text NOT NULL,
  `active` int(2) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
