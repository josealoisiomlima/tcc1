<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Serviços Oferecidos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f8bbd0, #ffe0e9);
            padding: 40px;
        }
        h1 {
            text-align: center;
            margin-bottom: 40px;
        }
        .servicos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
        }
        .servico {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 0 10px #ccc;
            width: 280px;
            text-align: center;
            padding: 20px;
            transition: transform 0.2s;
        }
        .servico:hover {
            transform: scale(1.03);
        }
        .servico img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .servico h2 {
            margin: 15px 0 10px;
            font-size: 20px;
        }
        .valor {
            font-size: 18px;
            color: #27ae60;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .profissional {
            font-size: 16px;
            color: #555;
        }
        .voltar {
            display: block;
            width: 200px;
            margin: 40px auto 0;
            text-align: center;
            padding: 12px 0;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .voltar:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

    <h1>Nossos Serviços</h1>

    <div class="servicos-container">
        <!-- Serviços aqui (mantido igual) -->
        <!-- ... os blocos de serviço que você já tem ... -->
        <div class="servico">
            <img src="daniela.jpg" alt="Daniela Fraga">
            <h2>Pedicure</h2>
            <div class="valor">R$ 40,00</div>
            <div class="profissional">com Daniela Fraga</div>
        </div>

        <div class="servico">
            <img src="milena.jpg" alt="Milena Lima">
            <h2>Corte</h2>
            <div class="valor">R$ 60,00</div>
            <div class="profissional">com Milena Lima</div>
        </div>

        <div class="servico">
            <img src="milena.jpg" alt="Milena Lima">
            <h2>Alisamento</h2>
            <div class="valor">R$ 120,00</div>
            <div class="profissional">com Milena Lima</div>
        </div>

        <div class="servico">
            <img src="milena.jpg" alt="Milena Lima">
            <h2>Pintura</h2>
            <div class="valor">R$ 100,00</div>
            <div class="profissional">com Milena Lima</div>
        </div>

        <div class="servico">
            <img src="roberta.jpg" alt="Roberta Santos">
            <h2>Design de Sobrancelhas</h2>
            <div class="valor">R$ 35,00</div>
            <div class="profissional">com Roberta Santos</div>
        </div>

        <div class="servico">
            <img src="maria.jpg" alt="Maria Parado">
            <h2>Estética Facial</h2>
            <div class="valor">R$ 90,00</div>
            <div class="profissional">com Maria Parado</div>
        </div>

        <div class="servico">
            <img src="maria.jpg" alt="Maria Parado">
            <h2>Estética Corporal</h2>
            <div class="valor">R$ 130,00</div>
            <div class="profissional">com Maria Parado</div>
        </div>

        <div class="servico">
            <img src="carol.jpg" alt="Carol Souza">
            <h2>Depilação</h2>
            <div class="valor">R$ 50,00</div>
            <div class="profissional">com Carol Souza</div>
        </div>
    </div>

    <a href="index.php" class="voltar"> Voltar para a Página Inicial</a>

</body>
</html>
