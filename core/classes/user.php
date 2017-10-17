<?php
/**
* 
*/


class User 
{
	protected $pdo;
	private $salt = '802ae2e14dec8189740aa497c944bb8e';
	
	function __construct()
	{
		$database = new Database();
		$pdo = $database->dbConnection();
		$this->pdo = $pdo;
	}

	public function securedPassword($password)
	{
		try {
			
			$password = hash('sha512', $password);
			$saltedPass = $this->salt . $password;
			$hashedPass = hash('sha512', $saltedPass);
			return $hashedPass;
		
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function runQuery($sql){
	
		$stmt = $this->pdo->prepare($sql);
		return $stmt;
	}

	public function checkInput($var){
		$var = htmlspecialchars($var);
		$var = trim($var);
		$var = stripcslashes($var);
		return $var;
	}

	public function login($email, $password){
		try{
				$password = $this->securedPassword($password);
				$stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = :email AND password = :password ");
				$stmt->bindParam(":email", $email, PDO::PARAM_STR);
				$stmt->bindParam(":password", $password, PDO::PARAM_STR);
				$stmt->execute();

				$user = $stmt->fetch(PDO::FETCH_OBJ);
				$count = $stmt->rowCount();

				if($count > 0){
					$_SESSION['user_id'] = $user->user_id;
					header('Location: home.php');
				} else {
					return false;
				}
	} catch(PDOException $e){
			echo $e->getMessage();
		}
	}

	public function checkEmail($email){
		$stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = :email");
		$stmt->bindParam(":email", $email, PDO::PARAM_STR);
		$stmt->execute();
		$count = $stmt->rowCount();

		if($count > 0){
			return true;
		} else {
			return false;
		}
	}

	public function checkUsername($username){
		$stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = :username");
		$stmt->bindParam(":username", $username, PDO::PARAM_STR);
		$stmt->execute();
		$count = $stmt->rowCount();

		if($count > 0){
			return true;
		} else {
			return false;
		}
	}

	public function search($search){
		try{

			$stmt = $this->pdo->prepare("SELECT user_id, username, screenName, profilePic, profileCover FROM users WHERE username LIKE ? OR screenName LIKE ?");
			$stmt->bindValue(1, $search.'%', PDO::PARAM_STR);
			$stmt->bindValue(2, '%'.$search.'%', PDO::PARAM_STR);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_OBJ);
		
		} catch(PDOException $e){
			echo $e->getMessage();
		}

	}

	public function create($table, $fields = array()){
		$coloumns = implode(',', array_keys($fields));
		$values = ':'.implode(', :', array_keys($fields));
		$sql = "INSERT INTO {$table} ({$coloumns}) VALUES ({$values}) ";
		if($stmt = $this->pdo->prepare($sql)){
			foreach ($fields as $key => $data) {
				$stmt->bindValue(':'.$key, $data);
			}
			$stmt->execute();
			return $this->pdo->lastInsertID();
		}
	}

	public function update($table, $user_id, $fields = array()){
		$coloumns = '';
		$i = 1;

		foreach ($fields as $name => $value) {
			$coloumns .= "{$name} = :{$name}";
			if($i < count($fields)){
				$coloumns .= ', ';
			}
			$i++;
		}
		$sql = "UPDATE {$table} SET {$coloumns } WHERE user_id = {$user_id} ";
		if($stmt = $this->pdo->prepare($sql)){
			foreach ($fields as $key => $value) {
				$stmt->bindValue(':'.$key, $value);
			}
			$stmt->execute();
		}

	}

	public function register($email, $screenName, $password){
		try{
			$password = md5($password);
			$stmt = $this->pdo->prepare("INSERT INTO users(email,password,screenName) VALUES(:email, :password, :screenName)");
			$stmt->bindParam(":email", $email, PDO::PARAM_STR);
			$stmt->bindParam(":password", $password, PDO::PARAM_STR);
			$stmt->bindParam(":screenName", $screenName, PDO::PARAM_STR);
			$stmt->execute();

			$user_id = $this->pdo->lastInsertID();
			$_SESSION['user_id'] = $user_id;

			return true;
		} catch(PDOException $e){
			echo $e->getMessage();
		}
	}


	public function userData($user_id){
		$stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
		$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_OBJ);
	}

	public function loggedIN(){
		return(isset($_SESSION['user_id'])) ? true : false;
	}


	public function logout(){
		$_SESSION = array();
		session_destroy();
		header('Location: '.BASE_URL.'index.php');
	}


	public function userIdByUsername($username){
		try{
			$stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = :username");
			$stmt->bindParam(":username", $username, PDO::PARAM_STR);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_OBJ);
			return $user->user_id;
		} catch(PDOException $e){
		echo $e->getMessage();
		}
	}

	public function uploadImage($file){
		$filename	= basename($file['name']);
		$fileTmp	= $file['tmp_name'];
		$filesize	= $file['size'];
		$error		= $file['error'];

		$ext 		 = explode('.', $filename);
		$ext 		 = strtolower(end($ext));
		$allowed_ext = array('jpg', 'jpeg', 'png');

		if(in_array($ext, $allowed_ext) === true){
			if($error == 0){
				if($filesize <= 209272152){
					$fileRoot = 'users/'.$filename;
					@move_uploaded_file($fileTmp, $fileRoot);
					return $fileRoot;
				} else {
					$GLOBALS['imageError'] = "File is loo large";
				}
			}
		} else {
			$GLOBALS['imageError'] = "The extension is not allowed";
		}
	} 
}
?>