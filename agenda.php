<?php
session_start();

// --- ATIVAR RELATÓRIO DE ERROS PHP (APENAS PARA DESENVOLVIMENTO) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ------------------------------------------------------------------

// Conexão com o banco de dados (idealmente, isso viria de um config.php)
// Se você já tem um config.php que define $conn, remova as próximas 5 linhas.
$host = "localhost";
$user = "root";
$pass = "";
$base = "usuarios"; // Nome do seu banco de dados
$conn = new mysqli($host, $user, $pass, $base);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_nome'])) {
    header("Location: login.php");
    exit;
}

$usuarioLogadoNome = $_SESSION['usuario_nome'];
$usuarioLogadoId = $_SESSION['usuario_id']; // Usar o ID do usuário para consultas

// Definindo especialidades e preços.
// É mais robusto buscar isso do banco de dados, mas para manter a funcionalidade existente, vou usar as arrays.
// Considere criar tabelas para 'servicos' e 'especialidades_profissionais' no futuro.
$especialidades = [
    "Daniela Fraga" => ["Pedicure"],
    "Milena Lima" => ["Corte", "Alisamento", "Pintura"],
    "Roberta Santos" => ["Design de Sobrancelhas"],
    "Maria Parado" => ["Estética Facial", "Estética Corporal"],
    "Carol Souza" => ["Depilação"]
];

$precos = [
    "Pedicure" => 30.00,
    "Corte" => 100.00,
    "Alisamento" => 100.00,
    "Pintura" => 100.00,
    "Design de Sobrancelhas" => 80.00,
    "Estética Facial" => 150.00,
    "Estética Corporal" => 150.00,
    "Depilação" => 120.00
];

// Lógica de cancelamento de agendamento
if (isset($_GET['cancelar'])) {
    $id = intval($_GET['cancelar']);
    // CORRIGIDO: Usar usuario (ID) e não o nome, para maior segurança
    $stmt = $conn->prepare("DELETE FROM agenda WHERE id = ? AND usuario = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $usuarioLogadoId); // 'ii' para dois inteiros (id do agendamento e id do usuário)
        $stmt->execute();
        $stmt->close();
    }
    header("Location: agenda.php");
    exit;
}

$editando = false;
$dados_edicao = [];
if (isset($_GET['editar'])) {
    $editando = true;
    $id_editar = intval($_GET['editar']);
    // CORRIGIDO: Usar usuario (ID) e não o nome, para maior segurança
    $stmt = $conn->prepare("SELECT * FROM agenda WHERE id = ? AND usuario = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id_editar, $usuarioLogadoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $dados_edicao = $res->fetch_assoc();
        $stmt->close();
    }
}

$mensagem = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $servico = $_POST['servico'] ?? '';
    $profissional_nome_selecionado = $_POST['profissional'] ?? ''; // Nome do profissional do formulário
    $preco = $precos[$servico] ?? 0;

    // Obter o ID do profissional com base no nome selecionado
    $profissional_id_selecionado = null;
    $stmt_get_prof_id = $conn->prepare("SELECT id FROM usuarios WHERE nome = ? AND nivel = 2"); // Nível 2 para profissionais
    if ($stmt_get_prof_id) {
        $stmt_get_prof_id->bind_param("s", $profissional_nome_selecionado);
        $stmt_get_prof_id->execute();
        $result_get_prof_id = $stmt_get_prof_id->get_result();
        if ($row_prof_id = $result_get_prof_id->fetch_assoc()) {
            $profissional_id_selecionado = $row_prof_id['id'];
        }
        $stmt_get_prof_id->close();
    }

    if (empty($data) || empty($hora) || empty($servico) || empty($profissional_nome_selecionado) || $profissional_id_selecionado === null) {
        $mensagem = "❌ Preencha todos os campos ou profissional inválido.";
    } elseif (strtotime($data) < strtotime(date("Y-m-d"))) {
        $mensagem = "❌ Não é possível agendar para datas passadas.";
    } else {
        if (isset($_POST['editar_id'])) {
            $id = intval($_POST['editar_id']);
            // CORRIGIDO: Atualizar profissional_id, não profissional
            $stmt = $conn->prepare("UPDATE agenda SET data=?, hora=?, servico=?, profissional_id=?, preco=? WHERE id=? AND usuario=?");
            if ($stmt) {
                // 'sssdiii': 2 strings, 1 double, 3 integers
                $stmt->bind_param("sssdiii", $data, $hora, $servico, $profissional_id_selecionado, $preco, $id, $usuarioLogadoId);
                if ($stmt->execute()) {
                    $mensagem = "✅ Agendamento atualizado com sucesso!"; // Mensagem de sucesso aqui, antes do redirecionamento
                    // header("Location: agenda.php"); // Removido para exibir a mensagem
                    // exit;
                } else {
                    $mensagem = "❌ Erro ao atualizar: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensagem = "❌ Erro na preparação da consulta de atualização: " . $conn->error;
            }
        } else {
            // VERIFICAÇÃO DE DISPONIBILIDADE
            // CORRIGIDO: Verificar profissional_id, não profissional
            $verifica = $conn->prepare("SELECT * FROM agenda WHERE data = ? AND hora = ? AND profissional_id = ?");
            if ($verifica) {
                $verifica->bind_param("ssi", $data, $hora, $profissional_id_selecionado); // 'ssi': 2 strings, 1 integer
                $verifica->execute();
                $resultado = $verifica->get_result();

                if ($resultado->num_rows > 0) {
                    $mensagem = "❌ Esse horário já está agendado com este profissional.";
                } else {
                    // INSERÇÃO DO NOVO AGENDAMENTO
                    // CORRIGIDO: Inserir usuario (ID) e profissional_id
                    $stmt = $conn->prepare("INSERT INTO agenda (usuario, data, hora, servico, profissional_id, preco, status_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $status_pagamento = 'aguardando'; // Definir um status inicial
                        // 'isssids': int (usuario), string (data), string (hora), string (servico), int (profissional_id), double (preco), string (status)
                        $stmt->bind_param("isssids", $usuarioLogadoId, $data, $hora, $servico, $profissional_id_selecionado, $preco, $status_pagamento);
                        if ($stmt->execute()) {
                            $mensagem = "✅ Agendamento realizado com sucesso!";
                        } else {
                            $mensagem = "❌ Erro ao agendar: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $mensagem = "❌ Erro na preparação da consulta de inserção: " . $conn->error;
                    }
                }
                $verifica->close();
            } else {
                $mensagem = "❌ Erro na preparação da consulta de verificação: " . $conn->error;
            }
        }
    }
}

// Buscar agendamentos para o cliente logado
// CORRIGIDO: JOIN com usuarios para pegar o nome do profissional pelo ID
$agendamentos = [];
$stmt_agenda = $conn->prepare("SELECT a.id, a.data, a.hora, a.servico, a.preco, u.nome AS nome_profissional
                               FROM agenda a
                               JOIN usuarios u ON a.profissional_id = u.id
                               WHERE a.usuario = ?
                               ORDER BY a.data ASC, a.hora ASC"); // Ordenar por data e hora futura
if ($stmt_agenda) {
    $stmt_agenda->bind_param("i", $usuarioLogadoId);
    $stmt_agenda->execute();
    $result_agenda = $stmt_agenda->get_result();
    if ($result_agenda) {
        while ($row = $result_agenda->fetch_assoc()) {
            $agendamentos[] = $row;
        }
    }
    $stmt_agenda->close();
} else {
    // Isso é um erro de preparação da query, não deveria acontecer em produção
    error_log("Erro na preparação da consulta de agendamentos do cliente: " . $conn->error);
}
// Fechar a conexão com o banco de dados (se não for fechada em config.php)
// $conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Agenda do Cliente</title>
    <style>
        /* Reset básico */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8bbd0, #ffe0e9);
            color: #333;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        a.btn-sair, a.btn-editar-usuario {
            background-color: #e91e63;
            color: white;
            padding: 10px 18px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            transition: background-color 0.3s ease;
            float: right;
        }
        a.btn-sair:hover, a.btn-editar-usuario:hover {
            background-color: #ad1457;
        }

        header {
            width: 100%;
            max-width: 900px;
            margin-bottom: 40px;
            display: flex;
            justify-content: flex-end;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 5px;
            color: #ad1457;
            text-align: center;
        }

        h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #ad1457;
            text-align: center;
        }

        form {
            background: #fff;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 900px;
            width: 100%;
            margin-bottom: 40px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            margin-top: 15px;
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ccc;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus,
        select:focus {
            border-color: #9c27b0;
            outline: none;
        }

        input[readonly] {
            background-color: #f3e5f5;
            color: #7b1fa2;
            font-weight: 600;
            cursor: default;
        }

        button {
            margin-top: 25px;
            background-color: #9c27b0;
            color: white;
            border: none;
            padding: 14px 0;
            border-radius: 40px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            font-weight: 700;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #7b1fa2;
        }

        .mensagem {
            max-width: 900px;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            max-width: 900px;
            text-align: center;
            user-select: none;
        }
        .mensagem:before {
            margin-right: 10px;
            font-size: 20px;
            vertical-align: middle;
        }
        .mensagem:empty {
            display: none;
        }

        /* Mensagens específicas */
        .mensagem:contains("✅") {
            background-color: #c8e6c9;
            border-left: 6px solid #2e7d32;
            color: #2e7d32;
        }
        .mensagem:contains("❌") {
            background-color: #ffcdd2;
            border-left: 6px solid #c62828;
            color: #c62828;
        }

        /* Tabela de agendamentos */
        table {
            width: 100%;
            max-width: 900px;
            border-collapse: separate;
            border-spacing: 0 10px;
            background: transparent;
        }

        th, td {
            background: white;
            padding: 14px 20px;
            text-align: left;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #555;
        }

        th {
            background: #9c27b0;
            color: white;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(156,39,176,0.6);
        }

        td {
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        td a {
            color: #9c27b0;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        td a:hover {
            color: #7b1fa2;
            text-decoration: underline;
        }

        /* Ações centralizadas e com espaçamento */
        td:last-child {
            white-space: nowrap;
            width: 140px;
        }

        /* Responsividade */
        @media (max-width: 720px) {
            form, table, header {
                padding: 20px;
                width: 100%;
                max-width: 100%;
            }

            input, select, button {
                max-width: 100%;
            }

            a.btn-sair, a.btn-editar-usuario {
                float: none;
                display: inline-block;
                margin: 5px 10px 20px;
            }

            td, th {
                font-size: 13px;
                padding: 10px 12px;
            }

            td:last-child {
                width: auto;
                white-space: normal;
            }
        }
    </style>
</head>
<body>

<header>
    <a href="logout.php" class="btn-sair">Sair</a>
    <a href="editar-usuario.php" class="btn-editar-usuario">Editar Usuário</a>
</header>

<h2>Olá, <?= htmlspecialchars($usuarioLogadoNome) ?>!</h2>
<h3><?= $editando ? 'Editar Agendamento' : 'Novo Agendamento' ?></h3>

<?php if ($mensagem): ?>
    <div class="mensagem"><?= $mensagem ?></div>
<?php endif; ?>

<form method="post" id="form-agendamento">
    <label for="usuario">Nome do Cliente:</label>
    <input type="text" id="usuario" value="<?= htmlspecialchars($usuarioLogadoNome) ?>" readonly />

    <input type="hidden" name="usuario_id_hidden" value="<?= htmlspecialchars($usuarioLogadoId) ?>" />

    <label for="data">Data:</label>
    <input type="date" name="data" id="data" value="<?= $editando ? htmlspecialchars($dados_edicao['data']) : '' ?>" required />

    <label for="hora">Hora:</label>
    <input type="time" name="hora" id="hora" value="<?= $editando ? htmlspecialchars($dados_edicao['hora']) : '' ?>" required />

    <label for="servico">Serviço:</label>
    <select name="servico" id="servico" required onchange="this.form.submit()">
        <option value="">Selecione</option>
        <?php
        $servicoAtual = $editando ? ($dados_edicao['servico'] ?? '') : ($_POST['servico'] ?? '');
        foreach ($precos as $serv => $val):
        ?>
            <option value="<?= htmlspecialchars($serv) ?>" <?= $serv == $servicoAtual ? 'selected' : '' ?>>
                <?= htmlspecialchars($serv) ?> (R$ <?= number_format($val, 2, ',', '.') ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label for="profissional">Profissional:</label>
    <select name="profissional" id="profissional" required>
        <option value="">Selecione</option>
        <?php
        $servico = $servicoAtual;
        if ($servico) {
            foreach ($especialidades as $profNome => $esp) {
                if (in_array($servico, $esp)) {
                    $selected = ($editando && $profNome == ($dados_edicao['nome_profissional'] ?? '')) ? 'selected' : '';
                    // No futuro, considere usar o ID do profissional no value para evitar consultas extras
                    echo "<option value='" . htmlspecialchars($profNome) . "' $selected>" . htmlspecialchars($profNome) . "</option>";
                }
            }
        }
        ?>
    </select>

    <?php if ($editando): ?>
        <input type="hidden" name="editar_id" value="<?= htmlspecialchars($dados_edicao['id']) ?>">
    <?php endif; ?>

    <button type="submit"><?= $editando ? 'Salvar Alterações' : 'Agendar' ?></button>
</form>

<h3>Meus Agendamentos</h3>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Hora</th>
            <th>Serviço</th>
            <th>Profissional</th>
            <th>Preço</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($agendamentos)): // Verificar se o array $agendamentos não está vazio ?>
        <?php foreach ($agendamentos as $row): // Iterar sobre o array $agendamentos ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                <td><?= date('H:i', strtotime($row['hora'])) ?></td>
                <td><?= htmlspecialchars($row['servico']) ?></td>
                <td><?= htmlspecialchars($row['nome_profissional']) ?></td>
                <td>R$ <?= number_format($row['preco'], 2, ',', '.') ?></td>
                <td>
                    <a href="?editar=<?= htmlspecialchars($row['id']) ?>" title="Editar">✏️</a>
                    &nbsp;|&nbsp;
                    <a href="?cancelar=<?= htmlspecialchars($row['id']) ?>" onclick="return confirm('Cancelar este agendamento?')" title="Cancelar">❌</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center; font-style: italic; color: #666;">Nenhum agendamento encontrado.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>