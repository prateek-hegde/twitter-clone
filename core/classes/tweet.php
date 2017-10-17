<?php
/**
* 
*/
class Tweet extends User
{
	
	function __construct()
	{
		$database = new Database();
		$pdo = $database->dbConnection();
		$this->pdo = $pdo;
	}

	public function tweets($user_id){
		$stmt = $this->pdo->prepare("SELECT * FROM tweets, users WHERE tweetBy = user_id ");
		$stmt->execute();
		$tweets = $stmt->fetchAll(PDO::FETCH_OBJ);

		foreach($tweets as $tweet){
			$likes = $this->likes($user_id, $tweet->tweetID);
			?>
				<div class="all-tweet">
				<div class="t-show-wrap">	
				 <div class="t-show-inner">
					<!-- this div is for retweet icon 
					<div class="t-show-banner">
						<div class="t-show-banner-inner">
							<span><i class="fa fa-retweet" aria-hidden="true"></i></span><span>Screen-Name Retweeted</span>
						</div>
					</div>
					-->
					<div class="t-show-popup">
						<div class="t-show-head">
							<div class="t-show-img">
								<img src="<?php echo $tweet->profilePic; ?>"/>
							</div>
							<div class="t-s-head-content">
								<div class="t-h-c-name">
									<span><a href="<?php echo $tweet->username; ?>"><?php echo $tweet->screenName; ?></a></span>
									<span><?php echo $tweet->username; ?></span>
									<span><?php echo $tweet->postedOn; ?></span>
								</div>
								<div class="t-h-c-dis">
									<?php echo $this->getTweetLinks($tweet->status); ?>
								</div>
							</div>
						</div>
						<!--tweet show head end-->
						<?php
							if(!empty($tweet->tweetImage)){
								echo '<div class="t-show-body">
									  <div class="t-s-b-inner">
									   <div class="t-s-b-inner-in">
									     <img src="'.$tweet->tweetImage.'" class="imagePopup"/>
									   </div>
									  </div>
									</div>';
							}
						?>
						<!--tweet show body end-->
					</div>
					<div class="t-show-footer">
						<div class="t-s-f-right">
							<ul> 
								<li><button><a href="#"><i class="fa fa-share" aria-hidden="true"></i></a></button></li>	
								<li><button><a href="#"><i class="fa fa-retweet" aria-hidden="true"></i></a></button></li>
								<li>
									<?php
										if($likes['likeOn'] === $tweet->tweetID){
											?>
												<button class="unlike-btn" data-user="<?php echo $tweet->tweetBy; ?>" data-tweet="<?php echo $tweet->tweetID; ?>"><a href="#"><i class="fa fa-heart" aria-hidden="true"></i></a><span class="likesCounter"></span></button>
											<?php
										} else {
											?>
												<button class="like-btn" data-user="<?php echo $tweet->tweetBy; ?>" data-tweet="<?php echo $tweet->tweetID; ?>"><a href="#"><i class="fa fa-heart-o" aria-hidden="true"></i></a><span class="likesCounter"><?php if($tweet->likesCount > 0) { echo $tweet->likesCount;} else echo " "; ?></span></button>
											<?php
										}
									?>
								</li>
									<li>
									<a href="#" class="more"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></a>
									<ul> 
									  <li><label class="deleteTweet">Delete Tweet</label></li>
									</ul>
								</li>
							</ul>
						</div>
					</div>
				</div>
				</div>
				</div>
			<?php
							
		}
	}

	public function getTrendByHash($hashtag){
		$stmt = $this->pdo->prepare("SELECT * FROM trends WHERE hashtag LIKE :hashtag");
		$stmt->bindValue(':hashtag', $hashtag.'%', PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}

	public function getMention($mention){
		$stmt = $this->pdo->prepare("SELECT user_id, username, screenName, profilePic FROM users WHERE username LIKE :mention OR screenName LIKE :mention");
		$stmt->bindValue(':mention', $mention.'%');
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}

	public function addTrend($hashtag){
		preg_match_all("/#+([a-zA-Z0-9_]+)/i", $hashtag, $matches);

		if($matches){
			$result = array_values($matches[1]);

			$sql = "INSERT INTO trends (hashtag, createdOn) VALUES (:hashtag, CURRENT_TIMESTAMP)";

				try{
					foreach ($result as $trend) {
						if($stmt = $this->pdo->prepare($sql)){
							$stmt->execute(array(':hashtag' => $trend ));
						}
					}
				} catch(PDOException $e){
					 $e->getMessage();
				}
		}
	}

	/*public function addTrend($hashtag){
        preg_match_all("/#+([a-zA-Z0-9_]+)/i", $hashtag, $matches);
        if($matches){
            $result = array_values($matches[1]);
        }
        $sql  = "INSERT INTO `trends` (`hashtag`,`createdOn`) VALUES(:hashtag, CURRENT_TIMESTAMP)";
        foreach ($result as $trend) {
            if($stmt = $this->pdo->prepare($sql)){
                $stmt->execute(array(':hashtag' => $trend));
            }
        }    
    }*/

	public function checkHashtag($hashtag){
		$stmt = $this->pdo->prepare("SELECT hashtag FROM trends WHERE hashtag = :hashtag");
		$stmt->bindParam(":hashtag", $hashtag, PDO::PARAM_STR);
		$stmt->execute();
		$count = $stmt->rowCount();

		if($count > 0){
			return true;
		} else {
			return false;
		}
	}

	public function getTweetLinks($tweet){
		$tweet = preg_replace("/(https?:\/\/)(\w)+.()[\w\.]+/", "<a href='$0' target='_blink'>$0</a>", $tweet);
		$tweet = preg_replace("/#([\w]+)/", "<a href='".BASE_URL."hashtag/$1'>$0</a>", $tweet);
		$tweet = preg_replace("/@([\w]+)/", "<a href='".BASE_URL."$1'>$0</a>", $tweet);
		return $tweet;
	}

	public function getPopupTweet($tweet_id,$user_id){
		$stmt = $this->pdo->prepare("SELECT * FROM tweets, users WHERE tweetID = :tweet_id AND tweetBy = :user_id");
		$stmt->bindParam(":tweet_id", $tweet_id, PDO::PARAM_INT);
		$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_OBJ);
	}

	public function addLike($user_id, $tweet_id, $get_id){
		try{
			$stmt = $this->pdo->prepare("UPDATE tweets SET likesCount = likesCount+1 WHERE tweetID = :tweet_id");
			$stmt->bindParam(":tweet_id", $tweet_id, PDO::PARAM_INT);
			$stmt->execute();
		} catch(PDOException $e){
			echo $e->getMessage();

		}

		try{
			$this->create('likes', array('likeBy' => $user_id, 'likeOn' => $tweet_id));
		}catch(PDOException $e){
			echo $e->getMessage();
		}
	}

	public function unlike($user_id, $tweet_id, $get_id){
		try{
			$stmt = $this->pdo->prepare("UPDATE tweets SET likesCount = likesCount-1 WHERE tweetID = :tweet_id");
			$stmt->bindParam(":tweet_id", $tweet_id, PDO::PARAM_INT);
			$stmt->execute();
		} catch(PDOException $e){
			echo $e->getMessage();

		}

		$stmt = $this->pdo->prepare("DELETE FROM likes WHERE likeBy = :user_id AND likeOn = :tweet_id");
		$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
		$stmt->bindParam(":tweet_id", $tweet_id, PDO::PARAM_INT);
		$stmt->execute();
	}

	public function likes($user_id, $tweet_id){
		$stmt = $this->pdo->prepare("SELECT * FROM likes WHERE likeBy = :user_id AND likeOn = :tweet_id");
		$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
		$stmt->bindParam(":tweet_id", $tweet_id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function tweetCount($user_id){
		$stmt = $this->pdo->prepare("SELECT * FROM tweets WHERE tweetBy = :user_id");
		$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->rowCount();

	}
}
?>