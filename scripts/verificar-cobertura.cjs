const { execSync } = require('child_process');
const fs = require('fs');

// Executa o script composer test:coverage que roda o Pest com Xdebug
try {
  console.log('Executando verificação de cobertura...');
  const out = execSync('composer run test:coverage', { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] });
  console.log(out);

  // Tenta encontrar a porcentagem total de coverage na saída.
  let percent = null;
  const totalMatch = out.match(/Total:\s*([0-9]{1,3}(?:\.[0-9]+)?)\s*%/i);
  if (totalMatch) {
    percent = parseFloat(totalMatch[1]);
  } else {
    const all = Array.from(out.matchAll(/([0-9]{1,3}(?:\.[0-9]+)?)\s*%/g));
    if (all.length > 0) {
      const last = all[all.length - 1];
      percent = parseFloat(last[1]);
    }
  }

  if (percent === null || Number.isNaN(percent)) {
    console.error('Não foi possível analisar a porcentagem de cobertura na saída.');
    process.exit(1);
  }

  console.log(`Cobertura detectada: ${percent}%`);

  const threshold = 80.0;
  if (percent < threshold) {
    console.error(`❌ Cobertura ${percent}% está abaixo do mínimo exigido de ${threshold}% — commit bloqueado.`);
    process.exit(1);
  }

  console.log(`✅ Cobertura ${percent}% atende ao mínimo de ${threshold}% — commit permitido.`);
  process.exit(0);
} catch (err) {
  if (err.stdout) console.error(err.stdout.toString());
  if (err.stderr) console.error(err.stderr.toString());
  console.error('Erro ao executar a verificação de cobertura', err.message);
  process.exit(1);
}
