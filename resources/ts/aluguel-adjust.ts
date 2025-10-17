import * as bootstrap from 'bootstrap';
import { showAlert } from './utils/alert';

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('adjustRentModal') as HTMLElement | null;
    const form = document.getElementById('adjustRentForm') as HTMLFormElement | null;
    const manualRadio = document.getElementById('adjustModeManual') as HTMLInputElement | null;
    const igpmRadio = document.getElementById('adjustModeIgpm') as HTMLInputElement | null;
    const manualGroup = document.getElementById('adjustManualGroup') as HTMLElement | null;
    const igpmGroup = document.getElementById('adjustIgpmGroup') as HTMLElement | null;
    const manualInput = document.getElementById('adjustManualValue') as HTMLInputElement | null;
    const currentValueEl = document.getElementById('adjustCurrentValue') as HTMLElement | null;
    const contractInfoEl = document.getElementById('adjustContractInfo') as HTMLElement | null;
    const previewBtn = document.getElementById('igpmPreviewBtn') as HTMLButtonElement | null;
    const previewResultEl = document.getElementById('igpmPreviewResult') as HTMLElement | null;
    const periodTextEl = document.getElementById('igpmPeriodText') as HTMLElement | null;
    const errorsEl = document.getElementById('adjustErrors') as HTMLElement | null;
    const submitBtn = document.getElementById('adjustSubmitBtn') as HTMLButtonElement | null;

    if (!modalEl || !form || !manualRadio || !igpmRadio) {
        return;
    }

    let currentAction = '';

    const getModalInstance = () => {
        return (
            (bootstrap.Modal.getOrCreateInstance && bootstrap.Modal.getOrCreateInstance(modalEl)) ||
            (bootstrap.Modal.getInstance && bootstrap.Modal.getInstance(modalEl)) ||
            new bootstrap.Modal(modalEl)
        );
    };

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';

    function toggleError(message?: string) {
        if (!errorsEl) {
            return;
        }
        if (!message) {
            errorsEl.textContent = '';
            errorsEl.classList.add('d-none');
            return;
        }
        errorsEl.textContent = message;
        errorsEl.classList.remove('d-none');
    }

    function formatCurrencyBR(value: number): string {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    function normalizeBrazilianNumber(input: string): string {
        let s = (input || '').trim();
        if (s === '') {
            return '';
        }
        s = s.replace(/\.(?=\d{3}(?:[^\d]|$))/g, '');
        s = s.replace(',', '.');
        return s;
    }

    function escapeHtml(unsafe: string): string {
        return unsafe.replace(/[&<>"']/g, function (c) {
            switch (c) {
                case '&': return '&amp;';
                case '<': return '&lt;';
                case '>': return '&gt;';
                case '"': return '&quot;';
                case "'": return '&#39;';
                default: return c;
            }
        });
    }

    function setModeUI() {
        const manualMode = manualRadio?.checked ?? false;
        if (manualGroup) {
            manualGroup.classList.toggle('d-none', !manualMode);
        }
        if (igpmGroup) {
            igpmGroup.classList.toggle('d-none', manualMode);
        }
        if (previewBtn) {
            previewBtn.disabled = manualMode;
        }
        if (manualMode && previewResultEl) {
            previewResultEl.textContent = '';
        }
        toggleError();
    }

    manualRadio.addEventListener('change', setModeUI);
    igpmRadio.addEventListener('change', setModeUI);

    document.querySelectorAll<HTMLElement>('.open-adjust-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            currentAction = btn.dataset.action ?? '';

            const rawValor = btn.dataset.valor ?? '';
            const valor = rawValor ? parseFloat(rawValor) : NaN;
            if (manualInput) {
                if (!Number.isNaN(valor)) {
                    manualInput.value = valor.toFixed(2).replace('.', ',');
                } else {
                    manualInput.value = '';
                }
            }

            const imovel = btn.dataset.imovel ?? '';
            const imovelNumero = btn.dataset.imovelNumero ?? '';
            const propriedade = btn.dataset.propriedade ?? '';
            const locatario = btn.dataset.locatario ?? '';
            const inicioBr = btn.dataset.dataInicioBr ?? '-';
            const fimBr = btn.dataset.dataFimBr ?? '-';
            const valorFmt = btn.dataset.valorFormatado ?? 'Valor não informado';

            if (currentValueEl) {
                currentValueEl.textContent = valorFmt;
            }

            if (contractInfoEl) {
                const parts: string[] = [];
                if (imovel) {
                    parts.push(imovelNumero ? `${imovel} (nº ${imovelNumero})` : imovel);
                }
                if (propriedade) {
                    parts.push(`Propriedade: ${propriedade}`);
                }
                if (locatario) {
                    parts.push(`Locatário: ${locatario}`);
                }
                parts.push(`Vigência: ${inicioBr} — ${fimBr}`);

                // render each part on its own line (escaped) with small muted paragraphs
                contractInfoEl.innerHTML = parts.map(p => `<p class="mb-0 small text-muted">${escapeHtml(p)}</p>`).join('');
            }

            if (periodTextEl) {
                periodTextEl.textContent = 'Selecione o modo IGP-M para calcular automaticamente.';
            }
            if (previewResultEl) {
                previewResultEl.textContent = '';
            }
            toggleError();

            manualRadio.checked = true;
            igpmRadio.checked = false;
            setModeUI();

            try {
                getModalInstance().show();
            } catch (err) {
                console.warn('[aluguel-adjust] falha ao abrir modal', err);
            }
        });
    });

    async function postAdjust(payload: URLSearchParams) {
        if (!currentAction) {
            throw new Error('Ação do formulário não configurada.');
        }

        const res = await fetch(currentAction, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': csrfToken
            },
            body: payload.toString()
        });

        return res;
    }

    previewBtn?.addEventListener('click', async () => {
        toggleError();
        if (!currentAction) {
            showAlert('error', 'Selecione um contrato antes de simular.');
            return;
        }

        const payload = new URLSearchParams();
        payload.set('_method', 'PATCH');
        payload.set('mode', 'igpm');
        payload.set('preview', '1');

        if (previewBtn) {
            previewBtn.disabled = true;
            previewBtn.textContent = 'Calculando...';
        }
        if (previewResultEl) {
            previewResultEl.textContent = '';
        }

        try {
            const res = await postAdjust(payload);
            if (!res.ok) {
                let message = 'Não foi possível calcular o IGP-M.';
                try {
                    const data = await res.json();
                    if (data && data.message) {
                        message = data.message;
                    }
                } catch (err) {}
                toggleError(message);
                return;
            }

            const data = await res.json();
            if (previewResultEl) {
                const perc = typeof data.igpm_percent === 'number' ? `${data.igpm_percent.toFixed(2)}%` : 'N/D';
                previewResultEl.textContent = `Novo valor estimado: ${data.new_value_formatted || ''} (IGP-M acumulado: ${perc})`;
            }
            if (periodTextEl && data.period) {
                periodTextEl.textContent = `Período considerado: ${data.period.start_br ?? ''} — ${data.period.end_br ?? ''}`;
            }
        } catch (err) {
            console.error('[aluguel-adjust] preview error', err);
            toggleError('Erro ao comunicar com o servidor.');
        } finally {
            if (previewBtn) {
                previewBtn.disabled = false;
                previewBtn.textContent = 'Simular novo valor';
            }
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        toggleError();

        if (!currentAction) {
            showAlert('error', 'Selecione um contrato para reajustar.');
            return;
        }

        const mode = manualRadio.checked ? 'manual' : 'igpm';
        const payload = new URLSearchParams();
        payload.set('_method', 'PATCH');
        payload.set('mode', mode);

        if (mode === 'manual') {
            const raw = manualInput?.value ?? '';
            const norm = normalizeBrazilianNumber(raw);
            if (!norm) {
                toggleError('Informe o novo valor do aluguel.');
                return;
            }
            const value = parseFloat(norm);
            if (!Number.isFinite(value) || value < 0) {
                toggleError('Valor inválido. Use apenas números e vírgula para centavos.');
                return;
            }
            payload.set('novo_valor', value.toFixed(2));
        }

        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Aplicando...';
            }

            const res = await postAdjust(payload);
            if (!res.ok) {
                let message = 'Não foi possível aplicar o reajuste.';
                try {
                    const data = await res.json();
                    if (data && data.message) {
                        message = data.message;
                    }
                    if (data && data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey && data.errors[firstKey]?.length) {
                            message = data.errors[firstKey][0];
                        }
                    }
                } catch (err) {}
                toggleError(message);
                return;
            }

            const data = await res.json();
            try {
                getModalInstance().hide();
            } catch (err) {}
            const msg = data.message || 'Aluguel reajustado com sucesso.';
            showAlert('success', msg, 1800);
            setTimeout(() => window.location.reload(), 900);
        } catch (err) {
            console.error('[aluguel-adjust] submit error', err);
            toggleError('Erro ao comunicar com o servidor.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Aplicar reajuste';
            }
        }
    });

    setModeUI();
});
