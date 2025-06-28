<?php
require_once("config.php");

if (isset($_POST["nome"], $_POST["email"], $_POST["senha"], $_POST["data_nasc"])) {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $data_nasc = $_POST["data_nasc"];

    $sql = "INSERT INTO usuarios (nome, email, senha, data_nasc) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Erro ao preparar SQL: " . $conn->error);
    }

    $stmt->bind_param("ssss", $nome, $email, $senha, $data_nasc);

    if ($stmt->execute()) {
        echo "<p>✅ Usuário cadastrado com sucesso!</p>";
        echo "<a href='login.php'>Ir para o login</a>";
    } else {
        echo "<p>❌ Erro ao cadastrar: " . $stmt->error . "</p>";
    }

    $stmt->close();
}
?>
