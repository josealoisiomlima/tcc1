<?php
session_start();
require_once("config.php"); // Certifique-se de que config.php está configurado corretamente e contém a conexão $conn

// --- ATIVAR RELATÓRIO DE ERROS PHP (APENAS PARA DESENVOLVIMENTO) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ------------------------------------------------------------------

// Restrição de acesso só para admin (nível 3)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] != 3) {
    header("Location: login.php");
    exit();
}

$mensagem = "";
$editando = false;
$profissional_editando = null;

// Processar cadastro ou edição do profissional
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST['acao'] ?? '';

    if ($acao === "cadastrar_profissional") {
        $nome = $_POST["nome"];
        $email = $_POST["email"];
        $senha_pura = $_POST["senha"];
        // ALTERADO: Usar password_hash para armazenar senhas de forma segura
        $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);
        $telefone = $_POST["telefone"];
        $especialidade = $_POST["especialidade"];
        $data_nasc = $_POST["data_nasc"];
        $nivel = 2; // Nível para profissional

        $sql = "INSERT INTO usuarios (nome, email, senha, telefone, especialidade, data_nasc, nivel)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // ALTERADO: Bind para a senha_hash
            $stmt->bind_param("ssssssi", $nome, $email, $senha_hash, $telefone, $especialidade, $data_nasc, $nivel);
            if ($stmt->execute()) {
                $mensagem = "✅ Profissional cadastrado com sucesso!";
            } else {
                $mensagem = "❌ Erro ao cadastrar: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensagem = "❌ Erro na preparação da consulta de cadastro: " . $conn->error;
        }
    } elseif ($acao === "editar_profissional") {
        $id = $_POST["id"];
        $nome = $_POST["nome"];
        $email = $_POST["email"];
        $telefone = $_POST["telefone"];
        $especialidade = $_POST["especialidade"];
        $data_nasc = $_POST["data_nasc"];

        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, especialidade=?, data_nasc=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssi", $nome, $email, $telefone, $especialidade, $data_nasc, $id);
            if ($stmt->execute()) {
                $mensagem = "✅ Profissional atualizado com sucesso!";
            } else {
                $mensagem = "❌ Erro ao atualizar: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensagem = "❌ Erro na preparação da consulta de edição: " . $conn->error;
        }
    }
}

// Exclusão de profissional
if (isset($_GET["excluir"])) {
    $id = $_GET["excluir"];

    // Antes de excluir o profissional, verificar se há agendamentos vinculados a ele
    $stmt_check_agenda = $conn->prepare("SELECT COUNT(*) FROM agenda WHERE profissional_id = ?");
    if ($stmt_check_agenda) {
        $stmt_check_agenda->bind_param("i", $id);
        $stmt_check_agenda->execute();
        $stmt_check_agenda->bind_result($count_agendamentos);
        $stmt_check_agenda->fetch();
        $stmt_check_agenda->close();

        if ($count_agendamentos > 0) {
            // ALTERADO: Não permitir exclusão se houver agendamentos. Oferecer opção de arquivar/inativar no futuro.
            $mensagem = "❌ Não é possível excluir o profissional. Ele possui agendamentos vinculados.";
            // Redireciona de volta para a lista de profissionais sem tentar excluir
            header("Location: adm.php?section=profissionais");
            exit();
        }
    } else {
        $mensagem = "❌ Erro na preparação da consulta de verificação de agendamentos: " . $conn->error;
    }


    // Se não houver agendamentos vinculados, procede com a exclusão
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND nivel = 2");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensagem = "🗑️ Profissional excluído com sucesso!";
        } else {
            $mensagem = "❌ Erro ao excluir profissional: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensagem = "❌ Erro na preparação da consulta de exclusão: " . $conn->error;
    }
    header("Location: adm.php?section=profissionais");
    exit();
}

// Preparar dados para edição
if (isset($_GET["editar"])) {
    $editando = true;
    $id = $_GET["editar"];
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? AND nivel = 2");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profissional_editando = $result->fetch_assoc();
        $stmt->close();
    } else {
        $mensagem = "❌ Erro na preparação para edição de profissional: " . $conn->error;
    }
}

// Buscar todos os profissionais para menu e listagem
$profissionais = [];
// ALTERADO: Selecionar TODAS as colunas necessárias para a listagem na tabela de profissionais
$res = $conn->query("SELECT id, nome, email, telefone, especialidade, data_nasc FROM usuarios WHERE nivel = 2 ORDER BY nome");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $profissionais[] = $row;
    }
} else {
    $mensagem = "❌ Erro ao buscar lista de profissionais: " . $conn->error;
}

// Verifica se foi selecionado um profissional para mostrar agenda
$profissional_selecionado_id = isset($_GET['profissional_id']) ? intval($_GET['profissional_id']) : null;
$agendamentos_profissional = [];
$nome_profissional_selecionado = ""; // Inicializa para evitar warnings

// --- ALTERAÇÃO PRINCIPAL: BUSCAR AGENDAMENTOS USANDO ID DO PROFISSIONAL ---
if ($profissional_selecionado_id) {
    // Buscar o nome do profissional selecionado (para exibição no título)
    foreach ($profissionais as $p_item) {
        if ($p_item['id'] == $profissional_selecionado_id) {
            $nome_profissional_selecionado = $p_item['nome'];
            break;
        }
    }

    // Consulta agendamentos usando profissional_id
    $stmt = $conn->prepare("
        SELECT a.*, u.nome AS nome_cliente, p.nome AS nome_profissional_agenda
        FROM agenda a
        JOIN usuarios u ON a.usuario = u.id
        JOIN usuarios p ON a.profissional_id = p.id
        WHERE a.profissional_id = ?
        ORDER BY a.data DESC, a.hora DESC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $profissional_selecionado_id); // "i" para ID (integer)
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res) {
                $agendamentos_profissional = $res->fetch_all(MYSQLI_ASSOC);
                if (count($agendamentos_profissional) === 0) {
                    $mensagem_agenda = "Sem agendamentos para este profissional.";
                }
            } else {
                $mensagem = "❌ Erro ao obter resultado da consulta de agendamentos: " . $conn->error;
            }
        } else {
            $mensagem = "❌ Erro ao executar consulta de agendamentos: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensagem = "❌ Erro na preparação da consulta de agendamentos: " . $conn->error;
    }
} else {
    $mensagem_agenda = "Selecione um profissional para visualizar a agenda.";
}
// ------------------------------------------------------------------


// --- Relatório Financeiro ---
$sql_financeiro = "
    SELECT p.nome AS nome_profissional, SUM(a.preco) AS total_servicos
    FROM agenda a
    JOIN usuarios p ON a.profissional_id = p.id
    GROUP BY p.id";
$result_financeiro = $conn->query($sql_financeiro);

$total_geral = 0;
$relatorio = [];
if ($result_financeiro) {
    while ($row = $result_financeiro->fetch_assoc()) {
        $nome = $row['nome_profissional'];
        $total = floatval($row['total_servicos']);
        $total_geral += $total;
        $relatorio[$nome] = $total;
    }
} else {
    $mensagem = "❌ Erro ao gerar relatório financeiro: " . $conn->error;
}


// Determina qual seção será exibida por padrão ou após uma ação
$active_section = $_GET['section'] ?? 'cadastro'; // Default to cadastro
if ($editando) {
    $active_section = 'cadastro'; // Se estiver editando, mostra a seção de cadastro
} elseif (isset($_GET["excluir"])) {
    $active_section = 'profissionais'; // Após excluir, mostra a lista de profissionais
} elseif ($profissional_selecionado_id) {
    $active_section = 'agenda'; // Se um profissional foi selecionado, mostra a agenda
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Painel do Administrador</title>
    <style>
        body { font-family: Arial; background-color: rgb(183, 212, 218); padding: 20px; }
        .header-menu {
            background: #4b0082;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-menu nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin-right: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .header-menu nav a:hover, .header-menu nav a.active {
            background-color: #360061;
        }
        .header-menu .logout {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .header-menu .logout:hover {
            background-color: #d9534f;
        }
        .container { background: #fff; padding: 30px; border-radius: 10px; max-width: 1100px; margin: auto; }
        .section-content { display: none; } /* Esconde todas as seções por padrão */
        .section-content.active { display: block; } /* Mostra a seção ativa */

        h2, h3 { color: #4b0082; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #eee; }
        .links a { margin-right: 10px; font-weight: bold; text-decoration: none; color: #4b0082; }
        .links a:hover { text-decoration: underline; }
        .mensagem { margin-bottom: 20px; font-weight: bold; color: green; }
        .mensagem.error { color: red; } /* Estilo para mensagens de erro */
        form label { display: block; margin-top: 10px; font-weight: bold; }
        form input, form button { width: 100%; padding: 8px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        form button { background: #4b0082; color: white; border: none; cursor: pointer; margin-top: 15px; }
        form button:hover { background: #360061; }

        .agenda-profissional-dropdown {
            margin-bottom: 20px;
        }
        .agenda-profissional-dropdown label {
            font-weight: bold;
            margin-right: 10px;
        }
        .agenda-profissional-dropdown select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div class="header-menu">
    <nav>
        <a href="#" class="menu-item <?= ($active_section === 'cadastro') ? 'active' : '' ?>" data-section="cadastro">Cadastro/Edição de Profissional</a>
        <a href="#" class="menu-item <?= ($active_section === 'profissionais') ? 'active' : '' ?>" data-section="profissionais">Listar Profissionais</a>
        <a href="#" class="menu-item <?= ($active_section === 'agenda') ? 'active' : '' ?>" data-section="agenda">Agenda por Profissional</a>
        <a href="#" class="menu-item <?= ($active_section === 'financeiro') ? 'active' : '' ?>" data-section="financeiro">Relatório Financeiro</a>
    </nav>
    <a href="logout.php" class="logout">Sair</a>
</div>

<div class="container">
    <?php if ($mensagem): ?>
        <div class="mensagem <?= (strpos($mensagem, '❌') !== false) ? 'error' : '' ?>"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <div id="cadastro" class="section-content <?= ($active_section === 'cadastro') ? 'active' : '' ?>">
        <h2>Cadastro de Profissional</h2>
        <form method="post" action="adm.php?section=cadastro">
            <input type="hidden" name="acao" value="<?= $editando ? "editar_profissional" : "cadastrar_profissional" ?>" />
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $profissional_editando['id'] ?>" />
            <?php endif; ?>

            <label>Nome:</label>
            <input required type="text" name="nome" value="<?= $editando ? htmlspecialchars($profissional_editando['nome']) : '' ?>" />

            <label>Email:</label>
            <input required type="email" name="email" value="<?= $editando ? htmlspecialchars($profissional_editando['email']) : '' ?>" />

            <?php if (!$editando): ?>
            <label>Senha:</label>
            <input required type="password" name="senha" placeholder="Senha para login" />
            <?php endif; ?>

            <label>Telefone:</label>
            <input type="text" name="telefone" value="<?= $editando ? htmlspecialchars($profissional_editando['telefone']) : '' ?>" />

            <label>Especialidade:</label>
            <input type="text" name="especialidade" value="<?= $editando ? htmlspecialchars($profissional_editando['especialidade']) : '' ?>" />

            <label>Data de Nascimento:</label>
            <input type="date" name="data_nasc" value="<?= $editando ? htmlspecialchars($profissional_editando['data_nasc']) : '' ?>" />

            <button type="submit"><?= $editando ? "Atualizar Profissional" : "Cadastrar Profissional" ?></button>
        </form>
    </div>

    <div id="profissionais" class="section-content <?= ($active_section === 'profissionais') ? 'active' : '' ?>">
        <h2>Profissionais Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Especialidade</th>
                    <th>Data Nasc.</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($profissionais as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['telefone']) ?></td>
                    <td><?= htmlspecialchars($p['especialidade']) ?></td>
                    <td><?= htmlspecialchars($p['data_nasc']) ?></td>
                    <td class="links">
                        <a href="adm.php?section=cadastro&editar=<?= $p['id'] ?>">Editar</a>
                        <a href="adm.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Confirma exclusão? Todos os agendamentos vinculados a este profissional DEVERÃO ser tratados ou ele não poderá ser excluído.')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="agenda" class="section-content <?= ($active_section === 'agenda') ? 'active' : '' ?>">
        <h2>Agenda do Profissional <?= htmlspecialchars($nome_profissional_selecionado) ?></h2>
        <div class="agenda-profissional-dropdown">
            <label for="select_profissional_agenda">Selecione o Profissional:</label>
            <select id="select_profissional_agenda" onchange="window.location.href='adm.php?section=agenda&profissional_id=' + this.value;">
                <option value="">-- Selecione --</option>
                <?php foreach ($profissionais as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($p['id'] == $profissional_selecionado_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (isset($mensagem_agenda)): ?>
            <p><?= htmlspecialchars($mensagem_agenda) ?></p>
        <?php elseif ($profissional_selecionado_id && count($agendamentos_profissional) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Preço (R$)</th>
                        <th>Status Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($agendamentos_profissional as $ag): ?>
                    <tr>
                        <td><?= htmlspecialchars($ag['nome_cliente']) ?></td>
                        <td><?= htmlspecialchars($ag['servico']) ?></td>
                        <td><?= date('d/m/Y', strtotime($ag['data'])) ?></td>
                        <td><?= htmlspecialchars($ag['hora']) ?></td>
                        <td><?= number_format($ag['preco'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($ag['status_pagamento'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="financeiro" class="section-content <?= ($active_section === 'financeiro') ? 'active' : '' ?>">
        <h2>Relatório Financeiro</h2>
        <table>
            <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Total Serviços (R$)</th>
                    <th>40% Salão (R$)</th>
                    <th>60% Profissional (R$)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($relatorio as $nome_prof => $total):
                $salon_share = $total * 0.4;
                $prof_share = $total * 0.6;
            ?>
                <tr>
                    <td><?= htmlspecialchars($nome_prof) ?></td>
                    <td><?= number_format($total, 2, ',', '.') ?></td>
                    <td><?= number_format($salon_share, 2, ',', '.') ?></td>
                    <td><?= number_format($prof_share, 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="font-weight:bold; background:#eee;">
                <td>Total Geral</td>
                <td><?= number_format($total_geral, 2, ',', '.') ?></td>
                <td><?= number_format($total_geral * 0.4, 2, ',', '.') ?></td>
                <td><?= number_format($total_geral * 0.6, 2, ',', '.') ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.menu-item');
        const sections = document.querySelectorAll('.section-content');

        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault(); // Impede o comportamento padrão do link

                // Remove a classe 'active' de todos os itens do menu e seções
                menuItems.forEach(i => i.classList.remove('active'));
                sections.forEach(s => s.classList.remove('active'));

                // Adiciona a classe 'active' ao item do menu clicado
                this.classList.add('active');

                // Pega o ID da seção a ser mostrada e adiciona a classe 'active'
                const targetSectionId = this.dataset.section;
                document.getElementById(targetSectionId).classList.add('active');

                // Atualiza a URL sem recarregar a página para manter o estado
                history.pushState(null, '', `adm.php?section=${targetSectionId}`);
            });
        });

        // Lógica para ativar a seção correta na carga inicial da página
        const urlParams = new URLSearchParams(window.location.search);
        const initialSection = urlParams.get('section') || 'cadastro'; // Pega a seção da URL ou default para 'cadastro'

        // Ativa o item do menu e a seção correspondente na carga da página
        const activeMenuItem = document.querySelector(`.menu-item[data-section="${initialSection}"]`);
        const activeSectionContent = document.getElementById(initialSection);

        if (activeMenuItem) {
            activeMenuItem.classList.add('active');
        }
        if (activeSectionContent) {
            activeSectionContent.classList.add('active');
        }

        // Lógica para manter a agenda do profissional selecionada quando a seção for recarregada
        const profissionalSelect = document.getElementById('select_profissional_agenda');
        if (profissionalSelect) {
            const profissionalIdFromUrl = urlParams.get('profissional_id');
            if (profissionalIdFromUrl) {
                profissionalSelect.value = profissionalIdFromUrl;
            }
        }
    });
</script>
</body>
</html>