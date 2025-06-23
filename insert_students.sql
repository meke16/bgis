CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(55) NOT NULL,
  `sex` enum('Female','Male') NOT NULL,
  `contact` varchar(55) NOT NULL,
  `username` varchar(100) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `password` varchar(100) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `photo` varchar(255) NOT NULL
)