
/* JS para gestionar la cesta de la compra */

const $d = document;

const $body              = $d.body;
const urlAcceso            = $body.dataset.urlAcceso;
const urlPedido            = $body.dataset.urlPedido;
const urlDatosCheckout     = $body.dataset.urlDatosCheckout;
const urlGuardarDireccion  = $body.dataset.urlGuardarDireccion;
const urlGuardarMetodoPago = $body.dataset.urlGuardarMetodoPago;
const tokenCsrf            = $body.dataset.tokenCsrf;
const tokenCsrfCheckout    = $body.dataset.tokenCsrfCheckout;
const estaAutenticado      = $body.dataset.autenticado === 'true';

/* Cesta */
const $panelCesta      = $d.querySelector('[data-panel-cesta]');
const $fondoCesta      = $d.querySelector('[data-fondo-cesta]');
const $articulosCesta  = $d.querySelector('[data-articulos-cesta]');
const $contadoresCesta = $d.querySelectorAll('[data-contador-cesta]');
const $totalCesta      = $d.querySelector('[data-total-cesta]');
const $btnFinalizar    = $d.querySelector('[data-finalizar-compra]');
const $btnVaciar       = $d.querySelector('[data-vaciar-cesta]');

/* Modal de asistente */
const $asistente      = $d.getElementById('asistente-compra');
const $fondoAsistente = $d.getElementById('fondo-asistente');
const $btnCancelar    = $d.getElementById('boton-cancelar');
const $btnAtras       = $d.getElementById('boton-atras');
const $btnSiguiente   = $d.getElementById('boton-siguiente');
const $btnConfirmar   = $d.getElementById('boton-confirmar');

/* Estado del asistente */
const estado = {
    paso:                 1,
    cargando:             false,
    direcciones:          [],
    metodosGuardados:     [],
    direccionEnvio:       null,
    direccionFacturacion: null,
    mismaFacturacion:     true,
    metodoPago:           null,
    idMetodoGuardado:     null,
    datosTarjetaNueva:    null,
    datosIbanNuevo:       null,
};


/**
 * Lógica de gestión de la cesta y del proceso de compra.
 */

const CLAVE_ALMACENAMIENTO = 'tienda_cesta';

function leerCesta() {
    try {
        const datos = JSON.parse(localStorage.getItem(CLAVE_ALMACENAMIENTO));
        return Array.isArray(datos) ? datos : [];
    } catch {
        return [];
    }
}

function guardarCesta(cesta) {
    localStorage.setItem(CLAVE_ALMACENAMIENTO, JSON.stringify(cesta));
}

function formatearPrecio(valor) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(valor);
}

function totalesCesta(cesta) {
    return cesta.reduce(
        (anterior, actual) => ({
            cantidad: anterior.cantidad + actual.cantidad,
            total:    anterior.total    + actual.precio * actual.cantidad,
        }),
        { cantidad: 0, total: 0 }
    );
}

function renderizarCesta() {
    const cesta = leerCesta();
    const { cantidad, total } = totalesCesta(cesta);

    $contadoresCesta.forEach(el => { el.textContent = cantidad; });
    if ($totalCesta) $totalCesta.textContent = formatearPrecio(total);

    if ($articulosCesta) {
        if (cesta.length === 0) {
            $articulosCesta.innerHTML = '<p class="cesta-vacia">' + window.t('cart.empty') + '</p>';
        } else {
            $articulosCesta.innerHTML = cesta.reduce((anterior, actual) => {
                return anterior + `
                <article class="cesta-item">
                    <div class="cesta-item-header">
                        <div>
                            <h3 class="cesta-item-titulo">${escaparHtml(actual.titulo)}</h3>
                            <p class="cesta-item-precio-ud">${formatearPrecio(actual.precio)} / ud.</p>
                        </div>
                        <button type="button" class="boton-quitar" data-quitar-id="${actual.id}"
                                aria-label="Quitar ${escaparHtml(actual.titulo)}">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                    <div class="cesta-item-footer">
                        <div class="controles-cantidad">
                            <button type="button" class="boton-cantidad" data-reducir-id="${actual.id}">−</button>
                            <span class="cantidad-valor">${actual.cantidad}</span>
                            <button type="button" class="boton-cantidad" data-aumentar-id="${actual.id}">+</button>
                        </div>
                        <strong class="cesta-item-total">${formatearPrecio(actual.precio * actual.cantidad)}</strong>
                    </div>
                </article>`;
            }, '');
        }
    }

    sincronizarBotonesProducto();
}

function agregarArticulo(producto) {
    const cesta = leerCesta();
    const existente = cesta.find(elemento => elemento.id === producto.id);
    if (existente) {
        existente.cantidad += 1;
    } else {
        cesta.push({ ...producto, cantidad: 1 });
    }
    guardarCesta(cesta);
    renderizarCesta();
}

function cambiarCantidad(id, delta) {
    const cesta    = leerCesta();
    const articulo = cesta.find(elemento => elemento.id === id);
    if (!articulo) return;
    articulo.cantidad += delta;
    guardarCesta(articulo.cantidad <= 0 ? cesta.filter(elemento => elemento.id !== id) : cesta);
    renderizarCesta();
}

function vaciarCesta() {
    if (leerCesta().length > 0) { guardarCesta([]); renderizarCesta(); }
}

function abrirCesta() {
    if ($panelCesta) { $panelCesta.classList.add('is-open'); $panelCesta.setAttribute('aria-hidden', 'false'); }
    if ($fondoCesta) $fondoCesta.classList.add('is-open');
    $body.style.overflow = 'hidden';
    renderizarCesta();
}

function cerrarCesta() {
    if ($panelCesta) { $panelCesta.classList.remove('is-open'); $panelCesta.setAttribute('aria-hidden', 'true'); }
    if ($fondoCesta) $fondoCesta.classList.remove('is-open');
    $body.style.overflow = '';
}


/* Asistente de compra */

function abrirAsistente() {
    if ($asistente)      $asistente.classList.add('is-open');
    if ($fondoAsistente) $fondoAsistente.classList.add('is-open');
    $body.style.overflow = 'hidden';
}

function cerrarAsistente() {
    if ($asistente)      $asistente.classList.remove('is-open');
    if ($fondoAsistente) $fondoAsistente.classList.remove('is-open');
    $body.style.overflow = '';
}

function irPaso(paso) {
    estado.paso = paso;

    $d.querySelectorAll('.panel-paso').forEach(panel => { panel.hidden = true; });
    $d.querySelectorAll('.indicador-paso').forEach(indicador => {
        indicador.classList.toggle('active', Number(indicador.dataset.paso) === paso);
        indicador.classList.toggle('done',   Number(indicador.dataset.paso) < paso);
    });

    const panelActivo = $d.getElementById(`paso-${paso}`);
    if (panelActivo) panelActivo.hidden = false;

    if ($btnAtras)     $btnAtras.hidden     = (paso === 1);
    if ($btnSiguiente) $btnSiguiente.hidden = (paso === 2);
    if ($btnConfirmar) $btnConfirmar.hidden = (paso !== 2);
}

// Función principal para iniciar el proceso de checkout: verifica autenticación, carga datos necesarios y muestra el asistente.
async function iniciarCheckout() {
    if (!estaAutenticado) { window.location.href = urlAcceso; return; }
    if (leerCesta().length === 0) { mostrarFlash(window.t('cart.empty'), 'aviso'); return; }

    irPaso(1);

    try {
        const respuesta = await fetch(urlDatosCheckout, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const datos     = await respuesta.json();
        if (!datos.ok) { window.location.href = datos.redirectUrl || urlAcceso; return; }
        estado.direcciones      = datos.direcciones  || [];
        estado.metodosGuardados = datos.metodosPago  || [];
    } catch {
        mostrarFlash(window.t('js.error.loading_data'), 'error');
        return;
    }

    renderizarPaso1();
    abrirAsistente();
}

// Selección de direcciones de envío y facturación.
function renderizarPaso1() {
    renderizarOpcionesDireccion('contenido-envio',       'dir_envio',       estado.direcciones);
    renderizarOpcionesDireccion('contenido-facturacion', 'dir_facturacion', estado.direcciones);
    actualizarVisibilidadFacturacion();
    conectarRadiosDireccion();
}

// Renderiza las opciones de direcciones para envío o facturación, incluyendo la opción de añadir una nueva direccion
function renderizarOpcionesDireccion(idContenedor, nombreRadio, direcciones) {
    const contenedor = $d.getElementById(idContenedor);
    if (!contenedor) return;

    let html = '<div class="cuadricula-dir">';

    direcciones.forEach(dir => {
        html += `
            <label class="tarjeta-direccion">
                <input type="radio" name="${nombreRadio}" value="${dir.id}">
                <div class="body-direccion">
                    <strong>${escaparHtml(dir.alias)}</strong>
                    <p>${escaparHtml(dir.calle)}</p>
                    <p>${escaparHtml(dir.codigoPostal)} ${escaparHtml(dir.ciudad)}, ${escaparHtml(dir.provincia)}</p>
                </div>
            </label>`;
    });

    html += `
        <label class="tarjeta-direccion tarjeta-direccion--nueva">
            <input type="radio" name="${nombreRadio}" value="nueva">
            <div class="body-direccion">
                <span class="material-icons">add_circle_outline</span>
                <strong>Nueva dirección</strong>
            </div>
        </label>
    </div>

    <div class="formulario-nueva-dir" id="${nombreRadio}-formulario-nuevo" hidden>
        <div class="fila-dos">
            <div class="grupo-campo">
                <label>Calle y número *</label>
                <input type="text" name="${nombreRadio}_calle" placeholder="Calle Mayor, 1" autocomplete="address-line1">
            </div>
            <div class="grupo-campo">
                <label>Código Postal *</label>
                <input type="text" name="${nombreRadio}_cp" placeholder="28001" maxlength="10" autocomplete="postal-code" inputmode="numeric">
            </div>
        </div>
        <div class="fila-dos">
            <div class="grupo-campo">
                <label>Ciudad *</label>
                <input type="text" name="${nombreRadio}_ciudad" placeholder="Madrid" autocomplete="address-level2">
            </div>
            <div class="grupo-campo">
                <label>Provincia</label>
                <input type="text" name="${nombreRadio}_provincia" placeholder="Madrid" autocomplete="address-level1">
            </div>
        </div>
        <div class="grupo-campo">
            <label>País</label>
            <input type="text" name="${nombreRadio}_pais" value="España" autocomplete="country-name">
        </div>
        <div class="grupo-campo">
            <label>Alias (opcional)</label>
            <input type="text" name="${nombreRadio}_alias" placeholder="Casa, Trabajo…">
        </div>
    </div>`;

    contenedor.innerHTML = html;

    if (direcciones.length > 0) {
        const primero = contenedor.querySelector(`input[name="${nombreRadio}"]`);
        if (primero) primero.checked = true;
    } else {
        const opcionNueva = contenedor.querySelector('input[value="nueva"]');
        if (opcionNueva) { opcionNueva.checked = true; mostrarFormularioNuevaDireccion(nombreRadio, true); }
    }
}

// Conecta los radios de selección de dirección para mostrar el formulario de nueva dirección cuando se elige esa opción, y para ocultarlo cuando se elige una dirección existente.
function conectarRadiosDireccion() {
    ['dir_envio', 'dir_facturacion'].forEach(nombre => {
        $d.querySelectorAll(`input[name="${nombre}"]`).forEach(radio => {
            radio.addEventListener('change', () => mostrarFormularioNuevaDireccion(nombre, radio.value === 'nueva'));
        });
    });

    const $checkMisma = $d.getElementById('misma-direccion');
    if ($checkMisma) $checkMisma.addEventListener('change', actualizarVisibilidadFacturacion);
}

function mostrarFormularioNuevaDireccion(nombreRadio, mostrar) {
    const $formulario = $d.getElementById(`${nombreRadio}-formulario-nuevo`);
    if ($formulario) $formulario.hidden = !mostrar;
}

function actualizarVisibilidadFacturacion() {
    const $check   = $d.getElementById('misma-direccion');
    const $seccion = $d.getElementById('seccion-facturacion');
    if (!$check || !$seccion) return;
    estado.mismaFacturacion = $check.checked;
    $seccion.hidden         = $check.checked;
}

function recogerDireccion(nombreRadio) {
    const seleccionado = $d.querySelector(`input[name="${nombreRadio}"]:checked`);
    if (!seleccionado) return null;

    if (seleccionado.value !== 'nueva') return parseInt(seleccionado.value, 10);

    const obtener = nombre => {
        const el = $d.querySelector(`input[name="${nombreRadio}_${nombre}"]`);
        return el ? el.value.trim() : '';
    };

    const calle = obtener('calle');
    if (!calle) return null;

    return {
        calle,
        codigoPostal:   obtener('cp'),
        ciudad:         obtener('ciudad'),
        provincia:      obtener('provincia'),
        pais:           obtener('pais') || 'España',
        alias:          obtener('alias') || null,
        nuevaDireccion: true,
    };
}

function validarPaso1() {
    const envio = recogerDireccion('dir_envio');
    if (!envio) { mostrarFlash(window.t('js.error.select_shipping'), 'aviso'); return false; }

    estado.direccionEnvio = envio;

    if (estado.mismaFacturacion) {
        estado.direccionFacturacion = envio;
    } else {
        const facturacion = recogerDireccion('dir_facturacion');
        if (!facturacion) { mostrarFlash(window.t('js.error.select_billing'), 'aviso'); return false; }
        estado.direccionFacturacion = facturacion;
    }

    return true;
}


// Selección de método de pago.

function renderizarPaso2() {
    renderizarMetodosGuardados('tarjetas-guardadas', 'metodo_tarjeta', estado.metodosGuardados.filter(m => m.tipo === 'TARJETA'));
    renderizarMetodosGuardados('cuentas-guardadas',  'metodo_cuenta',  estado.metodosGuardados.filter(m => m.tipo === 'CUENTA_BANCARIA'));

    $d.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
        radio.addEventListener('change', () => actualizarSeccionesPago(radio.value));
    });

    conectarRadiosMetodosGuardados('metodo_tarjeta', 'formulario-tarjeta', 'boton-nueva-tarjeta');
    conectarRadiosMetodosGuardados('metodo_cuenta',  'formulario-iban',    'boton-nueva-cuenta');

    $d.getElementById('boton-nueva-tarjeta')?.addEventListener('click', () => alternarFormularioNuevo('formulario-tarjeta', 'boton-nueva-tarjeta'));
    $d.getElementById('boton-nueva-cuenta')?.addEventListener('click',  () => alternarFormularioNuevo('formulario-iban',    'boton-nueva-cuenta'));

    const $numeroTarjeta = $d.getElementById('numero-tarjeta');
    if ($numeroTarjeta) {
        $numeroTarjeta.addEventListener('input', () => {
            $numeroTarjeta.value = $numeroTarjeta.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim().slice(0, 19);
        });
    }

    const $vencimiento = $d.getElementById('vencimiento');
    if ($vencimiento) {
        $vencimiento.addEventListener('input', () => {
            let valor = $vencimiento.value.replace(/\D/g, '');
            if (valor.length >= 2) valor = valor.slice(0, 2) + '/' + valor.slice(2, 4);
            $vencimiento.value = valor;
        });
    }

    actualizarSeccionesPago('');
}

// Renderiza los métodos de pago guardados 
function renderizarMetodosGuardados(idContenedor, nombreRadio, metodos) {
    const $contenedor = $d.getElementById(idContenedor);
    if (!$contenedor) return;
    if (metodos.length === 0) { $contenedor.innerHTML = ''; return; }

    $contenedor.innerHTML = metodos.reduce((anterior, actual) => {
        return anterior + `
            <label class="tarjeta-metodo">
                <input type="radio" name="${nombreRadio}" value="${actual.id}">
                <div class="body-metodo">
                    <strong>${escaparHtml(actual.alias)}</strong>
                    <span>${escaparHtml(actual.detalleMasked)}</span>
                </div>
            </label>`;
    }, '');

    const primero = $contenedor.querySelector(`input[name="${nombreRadio}"]`);
    if (primero) primero.checked = true;
}

function conectarRadiosMetodosGuardados(nombreRadio, idFormulario, idBoton) {
    $d.querySelectorAll(`input[name="${nombreRadio}"]`).forEach(radio => {
        radio.addEventListener('change', () => {
            const $formulario = $d.getElementById(idFormulario);
            const $boton      = $d.getElementById(idBoton);
            if ($formulario) $formulario.hidden = true;
            if ($boton)      $boton.style.display = '';
        });
    });
}

// Visibilidad del formulario de nueva tarjeta o cuenta
function alternarFormularioNuevo(idFormulario, idBoton) {
    const $formulario = $d.getElementById(idFormulario);
    const $boton      = $d.getElementById(idBoton);
    if (!$formulario) return;

    const estaVisible = !$formulario.hidden;
    $formulario.hidden = estaVisible;
    if ($boton) $boton.style.display = estaVisible ? '' : 'none';

    if (!estaVisible) {
        const mapaGrupos = { 'formulario-tarjeta': 'metodo_tarjeta', 'formulario-iban': 'metodo_cuenta' };
        $d.querySelectorAll(`input[name="${mapaGrupos[idFormulario]}"]`).forEach(radio => { radio.checked = false; });
    }
}

// Muestra la sección de datos correspondiente al método de pago seleccionado (tarjeta, transferencia o contrarreembolso) y oculta las demás
function actualizarSeccionesPago(metodo) {
    ['seccion-tarjeta', 'seccion-transferencia', 'seccion-contrarreembolso'].forEach(id => {
        const el = $d.getElementById(id);
        if (el) el.hidden = true;
    });

    const mapa = {
        TARJETA:          'seccion-tarjeta',
        TRANSFERENCIA:    'seccion-transferencia',
        CONTRARREEMBOLSO: 'seccion-contrarreembolso',
    };
    if (mapa[metodo]) {
        const el = $d.getElementById(mapa[metodo]);
        if (el) el.hidden = false;
    }
}

// Valida que se haya seleccionado un método de pago y que los datos introducidos sean correctos
function validarPaso2() {
    const $radioMetodo = $d.querySelector('input[name="metodo_pago"]:checked');
    if (!$radioMetodo) { mostrarFlash(window.t('js.error.select_payment'), 'aviso'); return false; }

    estado.metodoPago        = $radioMetodo.value;
    estado.idMetodoGuardado  = null;
    estado.datosTarjetaNueva = null;
    estado.datosIbanNuevo    = null;

    if (estado.metodoPago === 'TARJETA') {
        const $tarjetaGuardada = $d.querySelector('input[name="metodo_tarjeta"]:checked');
        if ($tarjetaGuardada) {
            estado.idMetodoGuardado = parseInt($tarjetaGuardada.value, 10);
        } else {
            const titular = $d.getElementById('titular')?.value.trim();
            const numero  = $d.getElementById('numero-tarjeta')?.value.replace(/\s/g, '');
            const venc    = $d.getElementById('vencimiento')?.value.trim();
            if (!titular || !numero || !venc) { mostrarFlash(window.t('js.error.fill_card'), 'aviso'); return false; }
            if (numero.length < 16) { mostrarFlash(window.t('js.error.invalid_card_number'), 'aviso'); return false; }
            const ultimos4    = numero.slice(-4);
            const enmascarado = `**** **** **** ${ultimos4}`;
            const guardar     = $d.getElementById('guardar-tarjeta')?.checked ?? true;
            estado.datosTarjetaNueva = { tipo: 'TARJETA', alias: titular || 'Mi tarjeta', detalleMasked: enmascarado, guardar };
        }
    }

    if (estado.metodoPago === 'TRANSFERENCIA') {
        const $cuentaGuardada = $d.querySelector('input[name="metodo_cuenta"]:checked');
        if ($cuentaGuardada) {
            estado.idMetodoGuardado = parseInt($cuentaGuardada.value, 10);
        } else {
            const iban  = $d.getElementById('numero-iban')?.value.trim().toUpperCase().replace(/\s/g, '');
            const alias = $d.getElementById('alias-iban')?.value.trim() || 'Mi cuenta';
            if (!iban) { mostrarFlash(window.t('js.error.enter_iban'), 'aviso'); return false; }
            const enmascarado = iban.slice(0, 4) + ' **** **** ' + iban.slice(-4);
            const guardar     = $d.getElementById('guardar-cuenta')?.checked ?? true;
            estado.datosIbanNuevo = { tipo: 'CUENTA_BANCARIA', alias, detalleMasked: enmascarado, guardar };
        }
    }

    return true;
}


// Envío del pedido a logística guardando los datos

async function confirmarPedido() {
    if (!validarPaso2()) return;

    if ($btnConfirmar) { $btnConfirmar.disabled = true; $btnConfirmar.textContent = 'Procesando…'; }

    try {
        const envioResuelto       = await resolverNuevaDireccion(estado.direccionEnvio);
        const facturacionResuelta = estado.mismaFacturacion
            ? envioResuelto
            : await resolverNuevaDireccion(estado.direccionFacturacion);

        if (estado.datosTarjetaNueva?.guardar) {
            const res = await guardarMetodoPago({ tipo: estado.datosTarjetaNueva.tipo, alias: estado.datosTarjetaNueva.alias, detalleMasked: estado.datosTarjetaNueva.detalleMasked });
            if (res?.id) estado.idMetodoGuardado = res.id;
        }
        if (estado.datosIbanNuevo?.guardar) {
            const res = await guardarMetodoPago({ tipo: estado.datosIbanNuevo.tipo, alias: estado.datosIbanNuevo.alias, detalleMasked: estado.datosIbanNuevo.detalleMasked });
            if (res?.id) estado.idMetodoGuardado = res.id;
        }

        const cesta     = leerCesta();
        const respuesta = await fetch(urlPedido, {
            method: 'POST',
            headers: {
                'Content-Type':    'application/json',
                'X-Requested-With':'XMLHttpRequest',
                'X-CSRF-TOKEN':    tokenCsrf,
            },
            body: JSON.stringify({
                items:           cesta,
                metodoPago:      estado.metodoPago,
                shippingAddress: envioResuelto,
                billingAddress:  estado.mismaFacturacion ? null : facturacionResuelta,
            }),
        });

        const datos = await respuesta.json();

        if (!respuesta.ok || !datos.ok) {
            if (datos.redirectUrl) { window.location.href = datos.redirectUrl; return; }
            mostrarFlash(datos.message || window.t('js.error.order_failed'), 'error');
            return;
        }

        vaciarCesta();
        cerrarCesta();
        cerrarAsistente();
        mostrarFlash(window.t('js.success.order_placed'), 'exito');
    } catch (error) {
        console.error(error);
        mostrarFlash(window.t('js.error.connection'), 'error');
    } finally {
        if ($btnConfirmar) {
            $btnConfirmar.disabled = false;
            $btnConfirmar.innerHTML = '<span class="material-icons">check</span> ' + window.t('checkout.confirm_order');
        }
    }
}

// Si la dirección es nueva se guarda su id y lo recoje, si existe obtiene el id 
async function resolverNuevaDireccion(dir) {
    if (dir === null) return null;
    if (typeof dir === 'number') return dir;

    if (dir.nuevaDireccion) {
        const respuesta = await fetch(urlGuardarDireccion, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': tokenCsrfCheckout },
            body: JSON.stringify(dir),
        });
        const datos = await respuesta.json();
        if (datos.ok) return datos.id;
    }

    return { calle: dir.calle, ciudad: dir.ciudad, codigoPostal: dir.codigoPostal, provincia: dir.provincia, pais: dir.pais, alias: dir.alias };
}

// Guarda un nuevo método de pago y devuelve su id para asociarlo al pedido.
async function guardarMetodoPago(datos) {
    try {
        const respuesta = await fetch(urlGuardarMetodoPago, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': tokenCsrfCheckout },
            body: JSON.stringify(datos),
        });
        return await respuesta.json();
    } catch {
        return null;
    }
}


// botones en las cards de productos para añadir o quitar productos sincronizado con la cesta
function renderizarBotonProducto(btnAgregar) {
    const id    = Number(btnAgregar.dataset.id);
    const stock = Number(btnAgregar.dataset.stock) || Infinity;
    const item  = leerCesta().find(el => el.id === id);
    const qty   = item ? item.cantidad : 0;

    if (qty > 0) {
        const maxAlcanzado = qty >= stock;
        btnAgregar.classList.add('modo-selector');
        btnAgregar.innerHTML =
            `<span class="sel-menos" data-tarjeta-reducir-id="${id}" aria-label="Quitar una unidad">` +
                `<span class="material-icons" aria-hidden="true">remove</span>` +
            `</span>` +
            `<span class="sel-qty" aria-live="polite" aria-atomic="true">${qty}</span>` +
            `<span class="sel-mas${maxAlcanzado ? ' sel-mas--max' : ''}"` +
                ` data-tarjeta-aumentar-id="${id}" aria-label="Añadir una unidad"` +
                `${maxAlcanzado ? ' aria-disabled="true"' : ''}>` +
                `<span class="material-icons" aria-hidden="true">add</span>` +
            `</span>`;
    } else {
        btnAgregar.classList.remove('modo-selector');
        btnAgregar.textContent = 'Añadir';
    }
}

function sincronizarBotonesProducto() {
    $d.querySelectorAll('[data-agregar-cesta]').forEach(renderizarBotonProducto);
}



const $flashContenedor = $d.getElementById('flash-contenedor');

function mostrarFlash(mensaje, tipo = 'aviso') {
    if (!$flashContenedor) return;
    const el = $d.createElement('p');
    el.className = `flash-mensaje flash-mensaje--${tipo}`;
    el.setAttribute('role', 'alert');
    const icono = tipo === 'exito' ? 'check_circle' : tipo === 'error' ? 'error' : 'warning';
    el.innerHTML =
        `<span class="material-icons" aria-hidden="true">${icono}</span>` +
        `<span>${escaparHtml(mensaje)}</span>`;
    $flashContenedor.appendChild(el);
    setTimeout(() => {
        el.classList.add('saliendo');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    }, tipo === 'exito' ? 5000 : 6000);
}

function escaparHtml(cadena) {
    return String(cadena ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}



$d.addEventListener('click', evento => {
    if (evento.target.closest('[data-abrir-cesta]'))  { abrirCesta();  return; }
    if (evento.target.closest('[data-cerrar-cesta]')) { cerrarCesta(); return; }
    if (evento.target.closest('[data-fondo-cesta]'))  { cerrarCesta(); return; }

    const $tarjetaMas = evento.target.closest('[data-tarjeta-aumentar-id]');
    if ($tarjetaMas) {
        if ($tarjetaMas.classList.contains('sel-mas--max')) return;
        const id  = Number($tarjetaMas.dataset.tarjetaAumentarId);
        const btn = $tarjetaMas.closest('[data-agregar-cesta]');
        if (btn) {
            const stock = Number(btn.dataset.stock) || Infinity;
            const item  = leerCesta().find(el => el.id === id);
            if (item && item.cantidad >= stock) return;
            agregarArticulo({
                id,
                titulo:    btn.dataset.titulo,
                precio:    Number(btn.dataset.precio),
                imagen:    btn.dataset.imagen,
                categoria: btn.dataset.categoria,
            });
        } else {
            cambiarCantidad(id, 1);
        }
        return;
    }

    const $tarjetaMenos = evento.target.closest('[data-tarjeta-reducir-id]');
    if ($tarjetaMenos) {
        cambiarCantidad(Number($tarjetaMenos.dataset.tarjetaReducirId), -1);
        return;
    }

    const $btnAgregar = evento.target.closest('[data-agregar-cesta]');
    if ($btnAgregar && !$btnAgregar.classList.contains('modo-selector')) {
        agregarArticulo({
            id:        Number($btnAgregar.dataset.id),
            titulo:    $btnAgregar.dataset.titulo,
            precio:    Number($btnAgregar.dataset.precio),
            imagen:    $btnAgregar.dataset.imagen,
            categoria: $btnAgregar.dataset.categoria,
        });
        return;
    }

    const $btnAumentar = evento.target.closest('[data-aumentar-id]');
    const $btnReducir  = evento.target.closest('[data-reducir-id]');
    const $btnQuitar   = evento.target.closest('[data-quitar-id]');

    if ($btnAumentar) cambiarCantidad(Number($btnAumentar.dataset.aumentarId),  1);
    if ($btnReducir)  cambiarCantidad(Number($btnReducir.dataset.reducirId),   -1);
    if ($btnQuitar) {
        guardarCesta(leerCesta().filter(elemento => elemento.id !== Number($btnQuitar.dataset.quitarId)));
        renderizarCesta();
    }
});

if ($btnFinalizar) $btnFinalizar.addEventListener('click', iniciarCheckout);
if ($btnVaciar)    $btnVaciar.addEventListener('click', vaciarCesta);

if ($btnCancelar)     $btnCancelar.addEventListener('click', cerrarAsistente);
if ($fondoAsistente)  $fondoAsistente.addEventListener('click', cerrarAsistente);

if ($btnAtras) $btnAtras.addEventListener('click', () => irPaso(1));

if ($btnSiguiente) $btnSiguiente.addEventListener('click', () => {
    if (!validarPaso1()) return;
    irPaso(2);
    renderizarPaso2();
});

if ($btnConfirmar) $btnConfirmar.addEventListener('click', confirmarPedido);

$d.addEventListener('keydown', evento => {
    if (evento.key !== 'Escape') return;
    if ($asistente?.classList.contains('is-open')) { cerrarAsistente(); return; }
    cerrarCesta();
});

renderizarCesta();


/** Gestión de cookies */

const COOKIE_CONSENT_KEY = 'cookies_consentimiento';
const $avisoCookies  = $d.getElementById('aviso-cookies');
const $btnAceptar    = $d.getElementById('cookies-aceptar');
const $btnRechazar   = $d.getElementById('cookies-rechazar');

function ocultarAviso() {
    if (!$avisoCookies) return;
    $avisoCookies.classList.remove('is-visible');
}

function guardarConsentimiento(tipo) {
    try { localStorage.setItem(COOKIE_CONSENT_KEY, tipo); } catch (_) {}
    ocultarAviso();
}

function inicializarConsentimiento() {
    if (!$avisoCookies) return;
    let guardado;
    try { guardado = localStorage.getItem(COOKIE_CONSENT_KEY); } catch (_) {}
    if (!guardado) {
        setTimeout(() => $avisoCookies.classList.add('is-visible'), 500);
    }
}

$btnAceptar?.addEventListener('click', () => guardarConsentimiento('todas'));
$btnRechazar?.addEventListener('click', () => guardarConsentimiento('necesarias'));

inicializarConsentimiento();
