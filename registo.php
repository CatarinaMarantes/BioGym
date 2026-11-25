<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/templates/common.php';

if (isset($_SESSION['id'])) redirecionar('/index.php');

// Valores escritos antes de um erro (guardados por action_registo.php)
$valores = isset($_SESSION['formRegisto']) ? $_SESSION['formRegisto'] : array();
unset($_SESSION['formRegisto']);
$v = function ($campo) use ($valores) {
    return isset($valores[$campo]) ? $valores[$campo] : '';
};

drawHeader('Registar');
?>

<section class="login-introducao">
    <h1>Faz parte do BioGym</h1>
    <p>O primeiro passo para uma vida mais ativa.</p>
</section>

<section class="formulario formulario-registo">

    <h2>Criar conta</h2>

    <p class="registo-descricao">
        Preenche os teus dados para começares.
    </p>

    <form action="/actions/action_registo.php" method="post">

        <?php drawCsrf(); ?>

        <div class="registo-campos">

            <!-- Nome -->
            <div class="registo-campo campo-completo">
                <label for="nome">Nome</label>

                <input type="text"
                       id="nome"
                       name="nome"
                       value="<?= e($v('nome')) ?>"
                       placeholder="Nome completo"
                       aria-describedby="erro-nome"
                       required>

                <small class="erro-campo" id="erro-nome">
                    Introduz o teu nome.
                </small>
            </div>

            <!-- Email -->
            <div class="registo-campo campo-completo">
                <label for="email">Email</label>

                <input type="email"
                       id="email"
                       name="email"
                       value="<?= e($v('email')) ?>"
                       placeholder="exemplo@email.com"
                       pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                       aria-describedby="erro-email"
                       required>

                <small class="erro-campo" id="erro-email">
                    Introduz um endereço de email válido.
                </small>
            </div>

            <!-- Telemóvel -->
            <div class="registo-campo">
                <label for="telemovel">Telemóvel</label>

                <input type="tel"
                       id="telemovel"
                       name="telemovel"
                       value="<?= e($v('telemovel')) ?>"
                       pattern="9[0-9]{8}"
                       placeholder="912345678"
                       aria-describedby="erro-telemovel"
                       required>

                <small class="erro-campo" id="erro-telemovel">
                    Deve ter 9 dígitos e começar por 9.
                </small>
            </div>

            <!-- Data de nascimento -->
            <div class="registo-campo">
                <label for="dataNascimento">
                    Data de nascimento
                </label>

                <input type="date"
                       id="dataNascimento"
                       name="dataNascimento"
                       value="<?= e($v('dataNascimento')) ?>"
                       max="<?= date('Y-m-d') ?>"
                       aria-describedby="erro-data"
                       required>

                <small class="erro-campo" id="erro-data">
                    Introduz uma data de nascimento válida,
                    não posterior a hoje.
                </small>
            </div>

            <!-- Palavra-passe -->
            <div class="registo-campo">
                <label for="password">Palavra-passe</label>

                <input type="password"
                       id="password"
                       name="password"
                       minlength="8"
                       aria-describedby="ajuda-password"
                       required>

                <small class="ajuda-campo" id="ajuda-password">
                    Mínimo de 8 caracteres.
                </small>
            </div>

            <!-- Confirmação -->
            <div class="registo-campo">
                <label for="confirmacao">
                    Confirmar palavra-passe
                </label>

                <input type="password"
                       id="confirmacao"
                       name="confirmacao"
                       minlength="8"
                       aria-describedby="erro-confirmacao"
                       required>

                <small class="erro-campo" id="erro-confirmacao">
                    Confirma a palavra-passe
                    com pelo menos 8 caracteres.
                </small>
            </div>

        </div>

        <button type="submit">Registar</button>

    </form>

    <p class="registo-login">
        Já tens conta?
        <a href="/login.php">Entrar</a>
    </p>

</section>

<?php drawFooter(); ?>
