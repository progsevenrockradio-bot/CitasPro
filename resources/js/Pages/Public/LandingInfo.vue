<template>
  <div class="lp" :style="cssVars">

    <!-- ══════════════════════════════════════════════════════
         DECORACIÓN DE FONDO GLOBAL
    ══════════════════════════════════════════════════════════ -->
    <div class="lp-bg-orb lp-bg-orb--blue" aria-hidden="true"></div>
    <div class="lp-bg-orb lp-bg-orb--violet" aria-hidden="true"></div>
    <div class="lp-bg-orb lp-bg-orb--cyan" aria-hidden="true"></div>

    <!-- ══════════════════════════════════════════════════════
         §1 — NAVEGACIÓN STICKY
    ══════════════════════════════════════════════════════════ -->
    <header class="lp-nav" :class="{ 'lp-nav--scrolled': scrolled }">
      <div class="lp-nav-inner">
        <!-- Logo -->
        <a href="/" class="lp-logo">
          <img v-if="configs.logo_url" :src="configs.logo_url" alt="CitasPro" class="lp-logo-img" />
          <div v-else class="lp-logo-icon">
            <span>C</span>
          </div>
          <span class="lp-logo-name">CitasPro</span>
        </a>

        <!-- Nav links desktop -->
        <nav class="lp-nav-links">
          <a href="#beneficios" class="lp-nav-link">Beneficios</a>
          <a href="#como-funciona" class="lp-nav-link">Cómo funciona</a>
          <a href="#sectores" class="lp-nav-link">Sectores</a>
          <a href="#planes" class="lp-nav-link">Planes</a>
        </nav>

        <!-- CTA nav -->
        <div class="lp-nav-cta">
          <router-link to="/panel" class="lp-btn lp-btn--ghost lp-btn--sm">Acceder</router-link>
          <router-link to="/registro" class="lp-btn lp-btn--primary lp-btn--sm">Comenzar gratis →</router-link>
        </div>

        <!-- Hamburger móvil -->
        <button class="lp-nav-burger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Menú">
          <span></span><span></span><span></span>
        </button>
      </div>

      <!-- Menú móvil -->
      <div v-if="mobileMenuOpen" class="lp-mobile-menu">
        <a href="#beneficios" class="lp-mobile-link" @click="mobileMenuOpen=false">Beneficios</a>
        <a href="#como-funciona" class="lp-mobile-link" @click="mobileMenuOpen=false">Cómo funciona</a>
        <a href="#sectores" class="lp-mobile-link" @click="mobileMenuOpen=false">Sectores</a>
        <a href="#planes" class="lp-mobile-link" @click="mobileMenuOpen=false">Planes</a>
        <router-link to="/registro" class="lp-btn lp-btn--primary" @click="mobileMenuOpen=false">Comenzar gratis</router-link>
      </div>
    </header>

    <main>

      <!-- ══════════════════════════════════════════════════════
           §2 — HERO
      ══════════════════════════════════════════════════════════ -->
      <section class="lp-hero reveal" :style="configs.hero_bg_url ? `background-image: linear-gradient(to bottom, rgba(8,12,24,0.85), #080C18), url(${configs.hero_bg_url}); background-size:cover;background-position:center;` : ''">
        <div class="lp-container">
          <div class="lp-hero-content">
            <!-- Badge -->
            <div class="lp-hero-badge">
              <span class="lp-hero-badge-dot"></span>
              {{ configs.hero_badge || 'Plataforma SaaS de Reservas Inteligentes' }}
            </div>

            <!-- Título -->
            <h1 class="lp-hero-title" :style="`font-family: var(--font-h1, 'Plus Jakarta Sans'), sans-serif;`"
                v-html="configs.hero_title || 'La forma más inteligente de<br><span class=\'lp-gradient-text\'>gestionar reservas</span> y crecer'">
            </h1>

            <!-- Subtítulo -->
            <p class="lp-hero-sub">
              {{ configs.hero_subtitle || 'CitasPro conecta clientes con profesionales mediante un sistema moderno de reservas, directorio inteligente y gestión de citas en tiempo real.' }}
            </p>

            <!-- CTAs -->
            <div class="lp-hero-actions">
              <router-link to="/registro" class="lp-btn lp-btn--primary lp-btn--lg">
                Comenzar ahora
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </router-link>
              <a href="#planes" class="lp-btn lp-btn--ghost lp-btn--lg">Ver planes</a>
            </div>

            <!-- Prueba social -->
            <div class="lp-hero-social-proof">
              <div class="lp-avatars">
                <div class="lp-avatar" style="background: linear-gradient(135deg, #3B82F6, #06B6D4);">A</div>
                <div class="lp-avatar" style="background: linear-gradient(135deg, #8B5CF6, #EC4899);">M</div>
                <div class="lp-avatar" style="background: linear-gradient(135deg, #06B6D4, #10B981);">L</div>
              </div>
              <span class="lp-social-text">+500 negocios ya confían en CitasPro</span>
            </div>
          </div>

          <!-- Ilustración Hero -->
          <div class="lp-hero-visual">
            <div class="lp-hero-card lp-hero-card--main">
              <div class="lp-hcard-header">
                <div class="lp-hcard-avatar" style="background: linear-gradient(135deg,#3B82F6,#06B6D4);">D</div>
                <div>
                  <div class="lp-hcard-name">Dr. Ana García</div>
                  <div class="lp-hcard-role">Clínica Dental · Madrid</div>
                </div>
                <div class="lp-hcard-badge lp-hcard-badge--green">● Online</div>
              </div>
              <div class="lp-hcard-slots">
                <div class="lp-slot lp-slot--taken">09:00</div>
                <div class="lp-slot lp-slot--taken">10:00</div>
                <div class="lp-slot lp-slot--free">11:00</div>
                <div class="lp-slot lp-slot--free lp-slot--selected">12:00</div>
                <div class="lp-slot lp-slot--free">14:00</div>
                <div class="lp-slot lp-slot--free">15:00</div>
              </div>
              <div class="lp-hcard-footer">
                <span>✓ Reserva confirmada</span>
                <span>Hoy · 12:00</span>
              </div>
            </div>

            <!-- Mini-cards flotantes -->
            <div class="lp-float-card lp-float-card--1">
              <span class="lp-fc-icon">📱</span>
              <div>
                <div class="lp-fc-title">WhatsApp enviado</div>
                <div class="lp-fc-sub">Recordatorio 24h antes</div>
              </div>
            </div>
            <div class="lp-float-card lp-float-card--2">
              <span class="lp-fc-icon">💳</span>
              <div>
                <div class="lp-fc-title">Pago recibido</div>
                <div class="lp-fc-sub">€49 · Stripe</div>
              </div>
            </div>
            <div class="lp-float-card lp-float-card--3">
              <span class="lp-fc-icon">📍</span>
              <div>
                <div class="lp-fc-title">+12 reseñas</div>
                <div class="lp-fc-sub">⭐ 4.9 / 5.0</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §3 — TRUST BELT (marquee)
      ══════════════════════════════════════════════════════════ -->
      <section class="lp-belt">
        <div class="lp-belt-label">Tecnología integrada</div>
        <div class="lp-belt-track">
          <div class="lp-belt-items">
            <span class="lp-belt-item">💳 Stripe</span>
            <span class="lp-belt-item">📱 WhatsApp</span>
            <span class="lp-belt-item">🗺️ Google Maps</span>
            <span class="lp-belt-item">📧 Email</span>
            <span class="lp-belt-item">📲 PWA</span>
            <span class="lp-belt-item">🔔 Push Notifications</span>
            <span class="lp-belt-item">📅 Calendario</span>
            <span class="lp-belt-item">📄 PDF</span>
            <span class="lp-belt-item">📊 Analytics</span>
            <span class="lp-belt-item">🔐 SSL/HTTPS</span>
            <span class="lp-belt-item">☁️ Cloudflare</span>
            <span class="lp-belt-item">🤖 IA</span>
            <!-- Duplicado para loop continuo -->
            <span class="lp-belt-item">💳 Stripe</span>
            <span class="lp-belt-item">📱 WhatsApp</span>
            <span class="lp-belt-item">🗺️ Google Maps</span>
            <span class="lp-belt-item">📧 Email</span>
            <span class="lp-belt-item">📲 PWA</span>
            <span class="lp-belt-item">🔔 Push Notifications</span>
            <span class="lp-belt-item">📅 Calendario</span>
            <span class="lp-belt-item">📄 PDF</span>
            <span class="lp-belt-item">📊 Analytics</span>
            <span class="lp-belt-item">🔐 SSL/HTTPS</span>
            <span class="lp-belt-item">☁️ Cloudflare</span>
            <span class="lp-belt-item">🤖 IA</span>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §4 — BENEFICIOS (composición asimétrica)
      ══════════════════════════════════════════════════════════ -->
      <section id="beneficios" class="lp-section">
        <div class="lp-container">
          <div class="lp-section-label reveal">POR QUÉ ELEGIR CITASPRO</div>
          <h2 class="lp-section-title reveal">Todo lo que necesita<br>tu negocio, en un solo lugar</h2>
          <p class="lp-section-sub reveal">Sin complicaciones. Sin contratos. Sin límites artificiales.</p>

          <!-- Fila 1: Grande + 2 pequeñas -->
          <div class="lp-benefits-row1">
            <div class="lp-benefit-big reveal">
              <div class="lp-benefit-icon" style="background:rgba(59,130,246,0.1);border-color:rgba(59,130,246,0.2);">⏱️</div>
              <h3 class="lp-benefit-title">Agenda Online 24/7</h3>
              <p class="lp-benefit-desc">Tus clientes reservan a cualquier hora, cualquier día, desde cualquier dispositivo. Sin llamadas, sin esperas, sin fricción.</p>
              <div class="lp-benefit-tag" style="color:#3B82F6;background:rgba(59,130,246,0.08);border-color:rgba(59,130,246,0.15);">Siempre disponible</div>
            </div>
            <div class="lp-benefits-col2">
              <div class="lp-benefit-small reveal">
                <div class="lp-benefit-icon-sm" style="background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.2);">📱</div>
                <div>
                  <h3 class="lp-benefit-title-sm">Recordatorios automáticos</h3>
                  <p class="lp-benefit-desc-sm">WhatsApp, SMS y email 24h y 1h antes. Reduce el no-show hasta un 70%.</p>
                </div>
              </div>
              <div class="lp-benefit-small reveal">
                <div class="lp-benefit-icon-sm" style="background:rgba(139,92,246,0.1);border-color:rgba(139,92,246,0.2);">💳</div>
                <div>
                  <h3 class="lp-benefit-title-sm">Cobros integrados</h3>
                  <p class="lp-benefit-desc-sm">Stripe Billing nativo. Cobra al reservar, cobra luego o cobra en efectivo. Tú decides.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Fila 2: 4 tarjetas medianas -->
          <div class="lp-benefits-row2">
            <div class="lp-benefit-med reveal" v-for="b in benefitsMed" :key="b.icon">
              <div class="lp-benefit-icon-sm" :style="`background:${b.bg};border-color:${b.border};`">{{ b.icon }}</div>
              <h3 class="lp-benefit-title-sm">{{ b.title }}</h3>
              <p class="lp-benefit-desc-sm">{{ b.desc }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §5 — CÓMO FUNCIONA (Timeline)
      ══════════════════════════════════════════════════════════ -->
      <section id="como-funciona" class="lp-section lp-section--alt">
        <div class="lp-container">
          <div class="lp-section-label reveal">PROCESO</div>
          <h2 class="lp-section-title reveal">Así funciona CitasPro</h2>
          <p class="lp-section-sub reveal">De la búsqueda a la cita completada en minutos.</p>

          <div class="lp-timeline">
            <div class="lp-timeline-line" ref="timelineLine"></div>
            <div
              v-for="(step, i) in timelineSteps" :key="i"
              :class="['lp-timeline-item', i % 2 === 0 ? 'lp-timeline-item--left' : 'lp-timeline-item--right', 'reveal']"
            >
              <div class="lp-timeline-node">
                <span class="lp-timeline-num">{{ String(i+1).padStart(2,'0') }}</span>
              </div>
              <div class="lp-timeline-card">
                <span class="lp-timeline-icon">{{ step.icon }}</span>
                <h3 class="lp-timeline-title">{{ step.title }}</h3>
                <p class="lp-timeline-desc">{{ step.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §6 — SECTORES
      ══════════════════════════════════════════════════════════ -->
      <section id="sectores" class="lp-section">
        <div class="lp-container">
          <div class="lp-section-label reveal">PARA QUIÉN</div>
          <h2 class="lp-section-title reveal">Diseñado para<br>tu industria</h2>
          <p class="lp-section-sub reveal">CitasPro se adapta a cualquier negocio que trabaje por cita previa.</p>

          <div class="lp-sectors-grid">
            <div
              v-for="sector in sectors" :key="sector.name"
              class="lp-sector-card reveal"
              :style="`--sec-color: ${sector.color};`"
            >
              <span class="lp-sector-icon">{{ sector.icon }}</span>
              <span class="lp-sector-name">{{ sector.name }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §7 — ESTADÍSTICAS
      ══════════════════════════════════════════════════════════ -->
      <section class="lp-stats" ref="statsSection">
        <div class="lp-container">
          <div class="lp-stats-grid">
            <div v-for="stat in stats" :key="stat.label" class="lp-stat reveal">
              <div class="lp-stat-num">
                <span class="lp-stat-prefix">{{ stat.prefix }}</span>
                <span class="lp-stat-value" :ref="el => statRefs[stat.label] = el" :data-target="stat.value">0</span>
                <span class="lp-stat-suffix">{{ stat.suffix }}</span>
              </div>
              <div class="lp-stat-label">{{ stat.label }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §8 — CARACTERÍSTICAS (Bento Grid)
      ══════════════════════════════════════════════════════════ -->
      <section class="lp-section lp-section--alt">
        <div class="lp-container">
          <div class="lp-section-label reveal">CARACTERÍSTICAS</div>
          <h2 class="lp-section-title reveal">Todo incluido.<br>Sin sorpresas.</h2>
          <p class="lp-section-sub reveal">Cada función diseñada para que tu negocio funcione solo.</p>

          <div class="lp-bento">
            <!-- Feature grande -->
            <div class="lp-bento-xl reveal">
              <div class="lp-bento-icon">📅</div>
              <h3 class="lp-bento-title">Calendario Inteligente</h3>
              <p class="lp-bento-desc">Vista diaria, semanal y mensual. Gestiona la agenda de múltiples profesionales en tiempo real. Arrastrar y soltar. Sin conflictos.</p>
              <div class="lp-bento-tag">Para el equipo completo</div>
            </div>

            <!-- Feature vertical -->
            <div class="lp-bento-v reveal" style="--fc:#10B981">
              <div class="lp-bento-icon">📱</div>
              <h3 class="lp-bento-title">WhatsApp Nativo</h3>
              <p class="lp-bento-desc">Confirmaciones, recordatorios y comprobantes en el número de teléfono del cliente. Automático.</p>
            </div>

            <!-- Feature vertical -->
            <div class="lp-bento-v reveal" style="--fc:#8B5CF6">
              <div class="lp-bento-icon">💳</div>
              <h3 class="lp-bento-title">Pagos Stripe</h3>
              <p class="lp-bento-desc">Cobra en línea al reservar, o en persona. Facturación automática y comprobantes PDF.</p>
            </div>

            <!-- Feature horizontal -->
            <div class="lp-bento-h reveal" style="--fc:#3B82F6">
              <div class="lp-bento-icon">🗺️</div>
              <div>
                <h3 class="lp-bento-title">Geolocalización GPS</h3>
                <p class="lp-bento-desc">Directorio inteligente con búsqueda por proximidad. Tus clientes te encuentran cerca de ellos.</p>
              </div>
            </div>

            <!-- Feature pequeña 1 -->
            <div class="lp-bento-s reveal" style="--fc:#F59E0B">
              <div class="lp-bento-icon">🔔</div>
              <h3 class="lp-bento-title">Push Web</h3>
              <p class="lp-bento-desc">Notificaciones nativas en móvil y escritorio.</p>
            </div>

            <!-- Feature pequeña 2 -->
            <div class="lp-bento-s reveal" style="--fc:#EC4899">
              <div class="lp-bento-icon">📲</div>
              <h3 class="lp-bento-title">PWA</h3>
              <p class="lp-bento-desc">Instala CitasPro como app nativa. Sin App Store.</p>
            </div>

            <!-- Feature horizontal abajo -->
            <div class="lp-bento-h reveal" style="--fc:#06B6D4">
              <div class="lp-bento-icon">👥</div>
              <div>
                <h3 class="lp-bento-title">Multiusuario y Roles</h3>
                <p class="lp-bento-desc">Administrador, profesional, recepcionista. Cada rol con sus permisos específicos.</p>
              </div>
            </div>

            <!-- Feature pequeña 3 -->
            <div class="lp-bento-s reveal" style="--fc:#10B981">
              <div class="lp-bento-icon">📊</div>
              <h3 class="lp-bento-title">Reportes</h3>
              <p class="lp-bento-desc">Ingresos, citas y métricas de negocio.</p>
            </div>

            <!-- Feature pequeña 4 -->
            <div class="lp-bento-s reveal" style="--fc:#3B82F6">
              <div class="lp-bento-icon">📄</div>
              <h3 class="lp-bento-title">PDF Automático</h3>
              <p class="lp-bento-desc">Comprobantes y recibos listos para imprimir.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §9 — PLANES (datos desde /api/web-config)
      ══════════════════════════════════════════════════════════ -->
      <section id="planes" class="lp-section">
        <div class="lp-container">
          <div class="lp-section-label reveal">PRECIOS</div>
          <h2 class="lp-section-title reveal">Elige tu plan.<br>Cancela cuando quieras.</h2>
          <p class="lp-section-sub reveal">Comienza gratis y mejora según tu negocio crece.</p>

          <div class="lp-plans-grid">
            <!-- FREE -->
            <div class="lp-plan reveal" v-if="configs.pricing_show_free !== 'false'">
              <div class="lp-plan-name">FREE</div>
              <div class="lp-plan-price">{{ configs.pricing_free_price || '0 €' }}<span>/mes</span></div>
              <ul class="lp-plan-features">
                <li>1 Profesional</li>
                <li>Hasta 15 citas al mes</li>
                <li>Portafolio visual</li>
                <li>Notificaciones por correo</li>
                <li class="lp-plan-miss">Sin recordatorios automáticos</li>
                <li class="lp-plan-miss">Sin pasarela de pagos</li>
              </ul>
              <router-link to="/registro" class="lp-btn lp-btn--ghost lp-btn--full">Empezar gratis</router-link>
            </div>

            <!-- BASIC -->
            <div class="lp-plan reveal">
              <div class="lp-plan-name">BASIC</div>
              <div class="lp-plan-price">{{ configs.pricing_basic_price || '13.99 €' }}<span>/mes</span></div>
              <ul class="lp-plan-features">
                <li>1 Profesional</li>
                <li>Hasta 60 citas al mes</li>
                <li>Recordatorios WhatsApp / SMS</li>
                <li>Comprobantes por correo</li>
                <li class="lp-plan-miss">Sin pasarela de pagos</li>
                <li class="lp-plan-miss">Sin personal extra</li>
              </ul>
              <router-link to="/registro" class="lp-btn lp-btn--ghost lp-btn--full">Contratar Basic</router-link>
            </div>

            <!-- PRO (destacado) -->
            <div :class="['lp-plan lp-plan--featured reveal', configs.pricing_pro_highlight !== 'false' ? 'lp-plan--featured' : '']">
              <div class="lp-plan-recommended">RECOMENDADO</div>
              <div class="lp-plan-name" style="color:#3B82F6">PRO</div>
              <div class="lp-plan-price">{{ configs.pricing_pro_price || '21.99 €' }}<span>/mes</span></div>
              <ul class="lp-plan-features">
                <li>Hasta 5 Profesionales</li>
                <li>Citas ilimitadas</li>
                <li>Pasarela de cobro integrada</li>
                <li>Recordatorios prioritarios</li>
                <li>Comprobantes en PDF</li>
                <li>Soporte estándar</li>
              </ul>
              <router-link to="/registro" class="lp-btn lp-btn--primary lp-btn--full">Mejorar a Pro</router-link>
            </div>

            <!-- ENTERPRISE -->
            <div class="lp-plan reveal">
              <div class="lp-plan-name">ENTERPRISE</div>
              <div class="lp-plan-price">{{ configs.pricing_enterprise_price || '27.99 €' }}<span>/mes</span></div>
              <ul class="lp-plan-features">
                <li>Multi-Sede (+5)</li>
                <li>Personal ilimitado</li>
                <li>Estadísticas avanzadas</li>
                <li>Soporte prioritario</li>
                <li>Reportes financieros</li>
                <li>Integración API personalizada</li>
              </ul>
              <a :href="configs.pricing_enterprise_contact || '/contacto'" class="lp-btn lp-btn--ghost lp-btn--full">Contactar ventas</a>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §10 — FAQ
      ══════════════════════════════════════════════════════════ -->
      <section class="lp-section lp-section--alt">
        <div class="lp-container lp-container--narrow">
          <div class="lp-section-label reveal">FAQ</div>
          <h2 class="lp-section-title reveal">Preguntas frecuentes</h2>

          <div class="lp-faq">
            <details v-for="(faq, i) in faqs" :key="i" class="lp-faq-item reveal">
              <summary class="lp-faq-q">
                {{ faq.q }}
                <span class="lp-faq-arrow">+</span>
              </summary>
              <p class="lp-faq-a">{{ faq.a }}</p>
            </details>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════
           §11 — CTA FINAL
      ══════════════════════════════════════════════════════════ -->
      <section class="lp-cta reveal">
        <div class="lp-cta-orb"></div>
        <div class="lp-container lp-container--narrow lp-cta-inner">
          <div class="lp-section-label" style="color:#93C5FD;background:rgba(59,130,246,0.08);border-color:rgba(59,130,246,0.15);">EMPIEZA HOY</div>
          <h2 class="lp-cta-title">¿Listo para que<br>tu negocio crezca?</h2>
          <p class="lp-cta-sub">Configura CitasPro en menos de 5 minutos. Sin tarjeta de crédito. Sin compromiso.</p>
          <div class="lp-cta-actions">
            <router-link to="/registro" class="lp-btn lp-btn--primary lp-btn--lg">
              Registrar mi negocio →
            </router-link>
            <a href="/directorio" class="lp-btn lp-btn--ghost lp-btn--lg">
              Buscar profesionales
            </a>
          </div>
        </div>
      </section>

    </main>

    <!-- ══════════════════════════════════════════════════════
         FOOTER
    ══════════════════════════════════════════════════════════ -->
    <footer class="lp-footer">
      <div class="lp-container">
        <div class="lp-footer-grid">
          <!-- Columna 1: Marca -->
          <div class="lp-footer-brand-col">
            <div class="lp-footer-logo">
              <img v-if="configs.logo_url" :src="configs.logo_url" alt="CitasPro" class="lp-logo-img" />
              <div v-else class="lp-logo-icon lp-logo-icon--sm"><span>C</span></div>
              <span>CitasPro</span>
            </div>
            <p class="lp-footer-tagline">La plataforma SaaS de reservas más moderna del mercado hispanohablante.</p>
            <div class="lp-footer-social">
              <a href="#" class="lp-social-btn" aria-label="Twitter/X">𝕏</a>
              <a href="#" class="lp-social-btn" aria-label="LinkedIn">in</a>
              <a href="#" class="lp-social-btn" aria-label="Instagram">ig</a>
            </div>
          </div>

          <!-- Columna 2: Producto -->
          <div class="lp-footer-col">
            <div class="lp-footer-col-title">Producto</div>
            <a href="#beneficios" class="lp-footer-link">Beneficios</a>
            <a href="#como-funciona" class="lp-footer-link">Cómo funciona</a>
            <a href="#planes" class="lp-footer-link">Planes y precios</a>
            <a href="#sectores" class="lp-footer-link">Para tu sector</a>
            <router-link to="/directorio" class="lp-footer-link">Directorio</router-link>
          </div>

          <!-- Columna 3: Empresa -->
          <div class="lp-footer-col">
            <div class="lp-footer-col-title">Empresa</div>
            <a href="#" class="lp-footer-link">Acerca de</a>
            <a href="#" class="lp-footer-link">Blog</a>
            <a href="#" class="lp-footer-link">Contacto</a>
            <a href="#" class="lp-footer-link">Socios</a>
          </div>

          <!-- Columna 4: Legal -->
          <div class="lp-footer-col">
            <div class="lp-footer-col-title">Legal</div>
            <a href="#" class="lp-footer-link">Privacidad</a>
            <a href="#" class="lp-footer-link">Términos de uso</a>
            <a href="#" class="lp-footer-link">Cookies</a>
            <a href="#" class="lp-footer-link">Centro de ayuda</a>
          </div>
        </div>

        <div class="lp-footer-bottom">
          <span>© 2026 CitasPro SaaS. Todos los derechos reservados.</span>
          <span class="lp-footer-made">Hecho con ♥ para negocios que crecen</span>
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// ─── Estado ──────────────────────────────────────────────
const configs         = ref({});
const scrolled        = ref(false);
const mobileMenuOpen  = ref(false);
const statsSection    = ref(null);
const timelineLine    = ref(null);
const statRefs        = ref({});
let   statsAnimated   = false;
let   scrollHandler   = null;

// ─── Datos estáticos ──────────────────────────────────────
const benefitsMed = [
  { icon: '🌍', title: 'Geolocalización', desc: 'Directorio inteligente por proximidad GPS. Tus clientes te encuentran cerca.', bg: 'rgba(6,182,212,0.1)', border: 'rgba(6,182,212,0.2)' },
  { icon: '🔔', title: 'Notificaciones Push', desc: 'Web push nativo en Android e iOS. Convierte visitas en clientes.', bg: 'rgba(245,158,11,0.1)', border: 'rgba(245,158,11,0.2)' },
  { icon: '📊', title: 'Panel Administrativo', desc: 'Dashboard con métricas clave, ingresos, citas y rendimiento del negocio.', bg: 'rgba(16,185,129,0.1)', border: 'rgba(16,185,129,0.2)' },
  { icon: '🌐', title: 'Directorio Inteligente', desc: 'Tu negocio visible para millones. Búsqueda con IA y geolocalización.', bg: 'rgba(139,92,246,0.1)', border: 'rgba(139,92,246,0.2)' },
];

const timelineSteps = [
  { icon: '🔍', title: 'Buscar', desc: 'El cliente busca un servicio en el directorio de CitasPro. Filtros por categoría, ubicación y valoraciones.' },
  { icon: '👤', title: 'Elegir', desc: 'Elige el profesional ideal viendo su portafolio, horario real y reseñas verificadas.' },
  { icon: '📅', title: 'Reservar', desc: 'Selecciona el día y hora disponibles. El sistema bloquea el turno en tiempo real.' },
  { icon: '✅', title: 'Confirmar', desc: 'Recibe confirmación instantánea por email y WhatsApp. Todo queda registrado.' },
  { icon: '📲', title: 'Recordatorio', desc: 'El sistema envía recordatorios automáticos 24h y 1h antes para reducir el no-show.' },
  { icon: '⭐', title: 'Servicio Realizado', desc: 'Cita completada. El cliente puede dejar una reseña y reservar de nuevo con un clic.' },
];

const sectors = [
  { icon: '🦷', name: 'Dentistas', color: '#3B82F6' },
  { icon: '🩺', name: 'Médicos', color: '#10B981' },
  { icon: '💇‍♀️', name: 'Peluquerías', color: '#EC4899' },
  { icon: '💈', name: 'Barberías', color: '#8B5CF6' },
  { icon: '🐾', name: 'Veterinarios', color: '#06B6D4' },
  { icon: '🧠', name: 'Psicólogos', color: '#A855F7' },
  { icon: '🏋️', name: 'Entrenadores', color: '#F59E0B' },
  { icon: '🧖‍♀️', name: 'Spas', color: '#EC4899' },
  { icon: '⚖️', name: 'Abogados', color: '#64748B' },
  { icon: '🎓', name: 'Academias', color: '#3B82F6' },
  { icon: '💼', name: 'Consultores', color: '#8B5CF6' },
  { icon: '🖥️', name: 'Freelancers', color: '#06B6D4' },
];

const stats = [
  { prefix: '+', value: 500, suffix: '', label: 'Negocios activos' },
  { prefix: '+', value: 10000, suffix: '', label: 'Reservas gestionadas' },
  { prefix: '+', value: 25, suffix: '', label: 'Categorías' },
  { prefix: '+', value: 15, suffix: '', label: 'Países' },
];

const faqs = [
  { q: '¿Necesito tarjeta de crédito para empezar?', a: 'No. El plan Free está disponible sin ningún método de pago. Actualiza cuando lo necesites.' },
  { q: '¿Puedo personalizar la apariencia de mi perfil?', a: 'Sí. Puedes subir tu logo, imagen de portada, colores de marca y describir tus servicios con detalle.' },
  { q: '¿Cómo funciona el sistema de recordatorios?', a: 'CitasPro envía automáticamente mensajes de WhatsApp, SMS y/o email 24 horas y 1 hora antes de cada cita. No necesitas hacer nada.' },
  { q: '¿Puedo tener varios profesionales en mi negocio?', a: 'Sí. Desde el plan Pro puedes gestionar hasta 5 profesionales con sus propias agendas. Enterprise no tiene límite.' },
  { q: '¿Los clientes necesitan crear una cuenta para reservar?', a: 'No. Los clientes pueden reservar directamente desde tu perfil sin registro. Opcionalmente, pueden guardar sus datos para futuras reservas.' },
  { q: '¿Puedo cancelar cuando quiera?', a: 'Sí, sin permanencia ni penalizaciones. Puedes cambiar de plan o cancelar desde tu panel en cualquier momento.' },
];

// ─── Configuración desde API ──────────────────────────────
onMounted(async () => {
  // Cargar configuración
  try {
    const res = await axios.get('/api/web-config');
    if (res.data.success) configs.value = res.data.data;
  } catch { /* silencioso */ }

  // Scroll: nav effect
  scrollHandler = () => { scrolled.value = window.scrollY > 30; };
  window.addEventListener('scroll', scrollHandler, { passive: true });

  // Scroll-reveal con IntersectionObserver
  const revealObserver = new IntersectionObserver(
    (entries) => entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); } }),
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );
  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

  // Contadores animados (se activan al entrar en viewport)
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting && !statsAnimated) {
        statsAnimated = true;
        stats.forEach(stat => {
          const el = statRefs.value[stat.label];
          if (!el) return;
          const duration = 1600;
          const start    = performance.now();
          const target   = stat.value;
          const animate  = (now) => {
            const elapsed  = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const ease     = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(ease * target).toLocaleString('es-ES');
            if (progress < 1) requestAnimationFrame(animate);
          };
          requestAnimationFrame(animate);
        });
      }
    });
  }, { threshold: 0.5 });

  if (statsSection.value) statsObserver.observe(statsSection.value);
});

onUnmounted(() => {
  if (scrollHandler) window.removeEventListener('scroll', scrollHandler);
});

// ─── CSS Variables dinámicas ──────────────────────────────
const cssVars = computed(() => ({
  '--font-primary': configs.value.font_primary || 'Inter',
  '--font-h1':      configs.value.font_h1      || 'Plus Jakarta Sans',
  '--font-h2':      configs.value.font_h2      || 'Plus Jakarta Sans',
}));
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   GOOGLE FONTS
═══════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap');

/* ═══════════════════════════════════════════════════════════
   VARIABLES GLOBALES
═══════════════════════════════════════════════════════════ */
.lp {
  --bg:          #080C18;
  --bg-alt:      #0D1120;
  --bg-card:     rgba(255,255,255,0.03);
  --border:      rgba(255,255,255,0.07);
  --border-blue: rgba(59,130,246,0.2);
  --text:        #F1F5F9;
  --text-muted:  #64748B;
  --blue:        #3B82F6;
  --cyan:        #06B6D4;
  --violet:      #8B5CF6;
  --green:       #10B981;
  --glow-blue:   0 0 40px rgba(59,130,246,0.18);
  --t:           200ms cubic-bezier(0.4,0,0.2,1);
  --t-slow:      500ms cubic-bezier(0.4,0,0.2,1);
  --radius:      20px;

  background-color: var(--bg);
  color: var(--text);
  font-family: var(--font-primary, 'Inter'), sans-serif;
  line-height: 1.6;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* ─── Orbes de fondo ──────────────────────────────────── */
.lp-bg-orb {
  position: fixed;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
  z-index: 0;
  opacity: 0.25;
}
.lp-bg-orb--blue   { width: 800px; height: 800px; background: radial-gradient(circle, rgba(59,130,246,0.4), transparent 70%); top: -200px; left: -200px; }
.lp-bg-orb--violet { width: 600px; height: 600px; background: radial-gradient(circle, rgba(139,92,246,0.3), transparent 70%); top: 40vh; right: -150px; }
.lp-bg-orb--cyan   { width: 500px; height: 500px; background: radial-gradient(circle, rgba(6,182,212,0.25), transparent 70%); bottom: 10vh; left: 30%; }

/* ─── Scroll reveal ───────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.65s cubic-bezier(0.16,1,0.3,1), transform 0.65s cubic-bezier(0.16,1,0.3,1);
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ─── Container ───────────────────────────────────────── */
.lp-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
  position: relative;
  z-index: 2;
}
.lp-container--narrow {
  max-width: 800px;
}

/* ─── Sección base ────────────────────────────────────── */
.lp-section { padding: 7rem 0; }
.lp-section--alt { background: linear-gradient(180deg, var(--bg-alt) 0%, var(--bg) 100%); }
.lp-section-label {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.9rem;
  border-radius: 999px;
  background: rgba(59,130,246,0.06);
  border: 1px solid rgba(59,130,246,0.12);
  color: #93C5FD;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  margin-bottom: 1.25rem;
}
.lp-section-title {
  font-family: var(--font-h2, 'Plus Jakarta Sans'), sans-serif;
  font-size: clamp(1.9rem, 3.5vw, 3rem);
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: #fff;
  margin-bottom: 1rem;
}
.lp-section-sub {
  font-size: 1.05rem;
  color: var(--text-muted);
  max-width: 520px;
  margin-bottom: 3.5rem;
}

/* ─── Gradiente de texto ──────────────────────────────── */
.lp-gradient-text, :deep(.lp-gradient-text) {
  background: linear-gradient(135deg, var(--blue) 0%, var(--cyan) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* ─── Botones ─────────────────────────────────────────── */
.lp-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.6rem;
  border-radius: 999px;
  font-weight: 700;
  font-size: 0.88rem;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: all var(--t);
  white-space: nowrap;
}
.lp-btn--primary {
  background: linear-gradient(135deg, var(--blue), var(--cyan));
  color: #fff;
  box-shadow: 0 4px 20px rgba(59,130,246,0.35);
}
.lp-btn--primary:hover { box-shadow: 0 6px 32px rgba(59,130,246,0.5); transform: translateY(-1px); }
.lp-btn--ghost {
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border);
  color: var(--text);
}
.lp-btn--ghost:hover { background: rgba(255,255,255,0.09); border-color: rgba(255,255,255,0.14); transform: translateY(-1px); }
.lp-btn--lg { padding: 0.95rem 2rem; font-size: 0.95rem; }
.lp-btn--sm { padding: 0.5rem 1.1rem; font-size: 0.78rem; }
.lp-btn--full { width: 100%; justify-content: center; }

/* ════════════════════════════════════════════════════════════
   §1 — NAVEGACIÓN
════════════════════════════════════════════════════════════ */
.lp-nav {
  position: sticky;
  top: 0;
  z-index: 100;
  transition: background var(--t), border-color var(--t), backdrop-filter var(--t);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}
.lp-nav--scrolled {
  background: rgba(8, 12, 24, 0.95);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border-bottom-color: rgba(255, 255, 255, 0.15);
}
.lp-nav-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  position: relative;
  z-index: 2;
}
/* Logo */
.lp-logo { display: flex; align-items: center; gap: 0.55rem; text-decoration: none; }
.lp-logo-img { height: 30px; object-fit: contain; }
.lp-logo-icon {
  width: 30px; height: 30px; border-radius: 8px;
  background: linear-gradient(135deg, var(--blue), var(--cyan));
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 900; color: #fff;
  box-shadow: 0 0 16px rgba(59,130,246,0.35);
}
.lp-logo-icon--sm { width: 22px; height: 22px; border-radius: 6px; font-size: 11px; }
.lp-logo-name { font-weight: 900; font-size: 1.05rem; color: #fff; letter-spacing: -0.02em; }
/* Nav links */
.lp-nav-links { display: flex; gap: 1.75rem; align-items: center; }
.lp-nav-link { color: rgba(255, 255, 255, 0.75); font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: color var(--t); }
.lp-nav-link:hover { color: #fff; }
/* Nav CTA */
.lp-nav-cta { display: flex; gap: 0.5rem; align-items: center; }
/* Hamburger */
.lp-nav-burger {
  display: none;
  flex-direction: column; gap: 5px; cursor: pointer;
  background: none; border: none; padding: 0.5rem;
}
.lp-nav-burger span {
  display: block; width: 22px; height: 2px;
  background: var(--text-muted); border-radius: 2px;
  transition: all var(--t);
}
/* Menú móvil */
.lp-mobile-menu {
  position: absolute;
  top: 100%; left: 0; right: 0;
  background: rgba(8,12,24,0.96);
  backdrop-filter: blur(24px);
  border-bottom: 1px solid var(--border);
  padding: 1.5rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  z-index: 99;
}
.lp-mobile-link {
  color: var(--text-muted); font-weight: 600;
  font-size: 0.9rem; text-decoration: none;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--border);
  transition: color var(--t);
}
.lp-mobile-link:hover { color: var(--text); }

@media (max-width: 900px) {
  .lp-nav-links, .lp-nav-cta { display: none; }
  .lp-nav-burger { display: flex; }
}

/* ════════════════════════════════════════════════════════════
   §2 — HERO
════════════════════════════════════════════════════════════ */
.lp-hero {
  min-height: 100svh;
  display: flex;
  align-items: center;
  padding: 6rem 0 5rem;
  position: relative;
  overflow: hidden;
}
.lp-hero .lp-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}
/* Contenido izquierdo */
.lp-hero-content { display: flex; flex-direction: column; align-items: flex-start; gap: 0; }
.lp-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 1rem;
  border-radius: 999px;
  background: rgba(59,130,246,0.07);
  border: 1px solid rgba(59,130,246,0.15);
  color: #93C5FD;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  margin-bottom: 1.5rem;
}
.lp-hero-badge-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--green);
  box-shadow: 0 0 6px rgba(16,185,129,0.7);
  animation: lp-pulse 2s infinite;
}
@keyframes lp-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.6; transform: scale(0.85); }
}
.lp-hero-title {
  font-family: var(--font-h1, 'Plus Jakarta Sans'), sans-serif;
  font-size: clamp(2.4rem, 5vw, 4.2rem);
  font-weight: 900;
  line-height: 1.08;
  letter-spacing: -0.03em;
  color: #fff;
  margin-bottom: 1.5rem;
}
.lp-hero-sub {
  font-size: 1.05rem;
  color: var(--text-muted);
  line-height: 1.7;
  max-width: 500px;
  margin-bottom: 2.25rem;
}
.lp-hero-actions { display: flex; gap: 0.85rem; flex-wrap: wrap; margin-bottom: 2.5rem; }
/* Prueba social */
.lp-hero-social-proof { display: flex; align-items: center; gap: 0.75rem; }
.lp-avatars { display: flex; }
.lp-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  border: 2px solid var(--bg);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 800; color: #fff;
  margin-left: -8px;
}
.lp-avatars .lp-avatar:first-child { margin-left: 0; }
.lp-social-text { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; }

/* ─── Ilustración Hero ─────────────────────────────────── */
.lp-hero-visual {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 420px;
}
/* Tarjeta principal */
.lp-hero-card {
  position: relative;
  z-index: 5;
  background: rgba(17,24,39,0.85);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius);
  padding: 1.5rem;
  width: 300px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.4), var(--glow-blue);
}
.lp-hero-card--main { animation: lp-float 4s ease-in-out infinite; }
@keyframes lp-float {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-10px); }
}
.lp-hcard-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; }
.lp-hcard-avatar {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; font-weight: 900; color: #fff; flex-shrink: 0;
}
.lp-hcard-name { font-size: 0.85rem; font-weight: 700; color: #fff; }
.lp-hcard-role { font-size: 0.68rem; color: var(--text-muted); }
.lp-hcard-badge {
  margin-left: auto; padding: 0.2rem 0.55rem;
  border-radius: 999px; font-size: 0.6rem; font-weight: 700;
}
.lp-hcard-badge--green { background: rgba(16,185,129,0.12); color: #34D399; border: 1px solid rgba(16,185,129,0.2); }
/* Slots de hora */
.lp-hcard-slots { display: grid; grid-template-columns: repeat(3,1fr); gap: 0.5rem; margin-bottom: 1.25rem; }
.lp-slot {
  padding: 0.45rem; border-radius: 10px;
  text-align: center; font-size: 0.72rem; font-weight: 700;
  transition: all var(--t);
}
.lp-slot--taken { background: rgba(255,255,255,0.04); color: var(--text-muted); text-decoration: line-through; opacity: 0.5; }
.lp-slot--free { background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.15); color: #93C5FD; cursor: pointer; }
.lp-slot--selected {
  background: linear-gradient(135deg, var(--blue), var(--cyan));
  color: #fff; border-color: transparent;
  box-shadow: 0 4px 12px rgba(59,130,246,0.35);
}
.lp-hcard-footer {
  display: flex; justify-content: space-between;
  padding-top: 1rem; border-top: 1px solid var(--border);
  font-size: 0.72rem;
}
.lp-hcard-footer span:first-child { color: var(--green); font-weight: 700; }
.lp-hcard-footer span:last-child { color: var(--text-muted); }
/* Float cards */
.lp-float-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.7rem 1rem;
  background: rgba(13,17,32,0.9);
  backdrop-filter: blur(16px);
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.3);
  z-index: 10;
  white-space: nowrap;
}
.lp-float-card--1 { top: 0; right: -20px; animation: lp-float-1 3.5s ease-in-out infinite; }
.lp-float-card--2 { bottom: 50px; right: -30px; animation: lp-float-2 4s ease-in-out infinite 0.5s; }
.lp-float-card--3 { bottom: 10px; left: -20px; animation: lp-float-1 3.8s ease-in-out infinite 1s; }
@keyframes lp-float-1 { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes lp-float-2 { 0%,100%{transform:translateY(0)} 50%{transform:translateY(8px)} }
.lp-fc-icon { font-size: 1.1rem; }
.lp-fc-title { font-size: 0.72rem; font-weight: 700; color: #fff; }
.lp-fc-sub { font-size: 0.62rem; color: var(--text-muted); }

@media (max-width: 960px) {
  .lp-hero .lp-container { grid-template-columns: 1fr; text-align: center; }
  .lp-hero-content { align-items: center; }
  .lp-hero-sub { text-align: center; }
  .lp-hero-visual { display: none; }
}

/* ════════════════════════════════════════════════════════════
   §3 — TRUST BELT
════════════════════════════════════════════════════════════ */
.lp-belt {
  padding: 2rem 0;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  background: rgba(255,255,255,0.01);
  overflow: hidden;
  text-align: center;
}
.lp-belt-label {
  font-size: 0.62rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--text-muted);
  margin-bottom: 1rem;
}
.lp-belt-track { overflow: hidden; mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); }
.lp-belt-items {
  display: flex;
  gap: 0;
  width: max-content;
  animation: lp-marquee 30s linear infinite;
}
@keyframes lp-marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
.lp-belt-item {
  padding: 0 2rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-muted);
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-right: 1px solid var(--border);
}

/* ════════════════════════════════════════════════════════════
   §4 — BENEFICIOS
════════════════════════════════════════════════════════════ */
.lp-benefits-row1 {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}
.lp-benefit-big {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2.5rem;
  transition: all var(--t-slow);
}
.lp-benefit-big:hover { border-color: var(--border-blue); box-shadow: var(--glow-blue); transform: translateY(-4px); }
.lp-benefit-icon {
  width: 52px; height: 52px; border-radius: 14px;
  border: 1px solid;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  margin-bottom: 1.25rem;
}
.lp-benefit-title { font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 0.65rem; }
.lp-benefit-desc  { font-size: 0.88rem; color: var(--text-muted); line-height: 1.65; margin-bottom: 1.25rem; }
.lp-benefit-tag {
  display: inline-flex; align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.65rem; font-weight: 700;
  border: 1px solid;
  letter-spacing: 0.06em;
}
.lp-benefits-col2 { display: flex; flex-direction: column; gap: 1.25rem; }
.lp-benefit-small {
  flex: 1;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  transition: all var(--t-slow);
}
.lp-benefit-small:hover { border-color: var(--border-blue); box-shadow: var(--glow-blue); transform: translateY(-3px); }
.lp-benefit-icon-sm {
  width: 40px; height: 40px; flex-shrink: 0; border-radius: 11px;
  border: 1px solid;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
}
.lp-benefit-title-sm { font-size: 0.9rem; font-weight: 700; color: #fff; margin-bottom: 0.35rem; }
.lp-benefit-desc-sm  { font-size: 0.8rem; color: var(--text-muted); line-height: 1.55; }
.lp-benefits-row2 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
}
.lp-benefit-med {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  transition: all var(--t-slow);
}
.lp-benefit-med:hover { border-color: var(--border-blue); transform: translateY(-4px); }
@media (max-width: 1024px) {
  .lp-benefits-row1 { grid-template-columns: 1fr; }
  .lp-benefits-row2 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 600px) {
  .lp-benefits-row2 { grid-template-columns: 1fr; }
}

/* ════════════════════════════════════════════════════════════
   §5 — TIMELINE
════════════════════════════════════════════════════════════ */
.lp-timeline {
  position: relative;
  max-width: 700px;
  margin: 0 auto;
  padding-top: 1rem;
}
.lp-timeline-line {
  position: absolute;
  top: 0; bottom: 0;
  left: 50%;
  width: 2px;
  background: linear-gradient(to bottom, var(--blue), var(--cyan), transparent);
  transform: translateX(-50%);
  transform-origin: top;
}
.lp-timeline-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 3rem;
  position: relative;
  z-index: 2;
}
.lp-timeline-item--left { justify-content: flex-end; padding-right: calc(50% + 2.5rem); }
.lp-timeline-item--right { justify-content: flex-start; padding-left: calc(50% + 2.5rem); }
.lp-timeline-node {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 44px; height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--blue), var(--cyan));
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 20px rgba(59,130,246,0.4);
  z-index: 10;
}
.lp-timeline-num { font-size: 0.7rem; font-weight: 900; color: #fff; }
.lp-timeline-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
  max-width: 260px;
  transition: all var(--t-slow);
}
.lp-timeline-card:hover { border-color: var(--border-blue); transform: scale(1.02); }
.lp-timeline-icon { font-size: 1.3rem; display: block; margin-bottom: 0.5rem; }
.lp-timeline-title { font-size: 0.95rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem; }
.lp-timeline-desc { font-size: 0.78rem; color: var(--text-muted); line-height: 1.55; }
@media (max-width: 700px) {
  .lp-timeline-line { left: 20px; }
  .lp-timeline-item--left,
  .lp-timeline-item--right { padding-left: 4rem; padding-right: 0; justify-content: flex-start; }
  .lp-timeline-node { left: 20px; }
  .lp-timeline-card { max-width: 100%; }
}

/* ════════════════════════════════════════════════════════════
   §6 — SECTORES
════════════════════════════════════════════════════════════ */
.lp-sectors-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
}
.lp-sector-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 0.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  cursor: default;
  transition: all var(--t-slow);
}
.lp-sector-card:hover {
  border-color: var(--sec-color, var(--blue));
  box-shadow: 0 0 24px color-mix(in srgb, var(--sec-color, var(--blue)) 25%, transparent);
  transform: translateY(-4px);
  background: rgba(255,255,255,0.04);
}
.lp-sector-icon { font-size: 1.5rem; }
.lp-sector-name { font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-align: center; transition: color var(--t); }
.lp-sector-card:hover .lp-sector-name { color: var(--text); }
@media (max-width: 960px) { .lp-sectors-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 600px) { .lp-sectors-grid { grid-template-columns: repeat(3, 1fr); } }

/* ════════════════════════════════════════════════════════════
   §7 — ESTADÍSTICAS
════════════════════════════════════════════════════════════ */
.lp-stats {
  padding: 5rem 0;
  background: linear-gradient(135deg, rgba(59,130,246,0.06) 0%, rgba(6,182,212,0.04) 100%);
  border-top: 1px solid rgba(59,130,246,0.1);
  border-bottom: 1px solid rgba(59,130,246,0.1);
}
.lp-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; text-align: center; }
.lp-stat { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.lp-stat-num { font-family: var(--font-h2,'Plus Jakarta Sans'), sans-serif; font-size: 3rem; font-weight: 900; line-height: 1; color: #fff; display: flex; align-items: baseline; gap: 0.1em; }
.lp-stat-prefix, .lp-stat-suffix { font-size: 1.5rem; font-weight: 700; color: var(--blue); }
.lp-stat-value { background: linear-gradient(135deg, var(--blue), var(--cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.lp-stat-label { font-size: 0.82rem; color: var(--text-muted); font-weight: 600; }
@media (max-width: 700px) { .lp-stats-grid { grid-template-columns: repeat(2, 1fr); } }

/* ════════════════════════════════════════════════════════════
   §8 — BENTO GRID
════════════════════════════════════════════════════════════ */
.lp-bento {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: 180px;
  gap: 1.25rem;
  grid-auto-flow: dense;
}
.lp-bento > div {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  transition: all var(--t-slow);
  overflow: hidden;
  position: relative;
}
.lp-bento > div::before {
  content:'';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 70% 30%, color-mix(in srgb, var(--fc, var(--blue)) 12%, transparent), transparent 60%);
  pointer-events: none;
}
.lp-bento > div:hover {
  border-color: var(--fc, var(--blue));
  box-shadow: 0 0 30px color-mix(in srgb, var(--fc, var(--blue)) 20%, transparent);
  transform: translateY(-4px);
}
.lp-bento-xl { grid-column: span 2; grid-row: span 2; }
.lp-bento-v  { grid-column: span 1; grid-row: span 2; }
.lp-bento-h  { grid-column: span 2; grid-row: span 1; display: flex; align-items: center; gap: 1.25rem; }
.lp-bento-h::before { display: none; }
.lp-bento-s  { grid-column: span 1; grid-row: span 1; }
.lp-bento-icon { font-size: 1.75rem; margin-bottom: 0.75rem; display: block; }
.lp-bento-h .lp-bento-icon { font-size: 2rem; margin-bottom: 0; flex-shrink: 0; }
.lp-bento-title { font-size: 0.95rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem; }
.lp-bento-xl .lp-bento-title { font-size: 1.2rem; }
.lp-bento-desc  { font-size: 0.78rem; color: var(--text-muted); line-height: 1.6; }
.lp-bento-tag {
  display: inline-flex; margin-top: 1rem;
  padding: 0.25rem 0.7rem; border-radius: 999px;
  background: rgba(59,130,246,0.08);
  border: 1px solid rgba(59,130,246,0.15);
  color: #93C5FD; font-size: 0.62rem; font-weight: 700;
}
@media (max-width: 900px) {
  .lp-bento { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 160px; }
  .lp-bento-xl { grid-column: span 2; }
}
@media (max-width: 600px) {
  .lp-bento { grid-template-columns: 1fr; grid-auto-rows: auto; }
  .lp-bento-xl, .lp-bento-v, .lp-bento-h, .lp-bento-s { grid-column: span 1; grid-row: span 1; min-height: 130px; }
}

/* ════════════════════════════════════════════════════════════
   §9 — PLANES
════════════════════════════════════════════════════════════ */
.lp-plans-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  align-items: stretch;
}
.lp-plan {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0;
  position: relative;
  transition: all var(--t-slow);
}
.lp-plan:hover { transform: translateY(-4px); border-color: rgba(255,255,255,0.12); }
.lp-plan--featured {
  background: linear-gradient(160deg, rgba(59,130,246,0.07) 0%, var(--bg-card) 100%);
  border-color: rgba(59,130,246,0.35);
  box-shadow: 0 12px 40px rgba(59,130,246,0.15);
}
.lp-plan--featured:hover { box-shadow: 0 20px 60px rgba(59,130,246,0.25); border-color: var(--blue); }
.lp-plan-recommended {
  position: absolute;
  top: -12px; left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, var(--blue), var(--cyan));
  color: #fff;
  padding: 0.2rem 0.9rem;
  border-radius: 999px;
  font-size: 0.6rem; font-weight: 800; letter-spacing: 0.1em;
  white-space: nowrap;
}
.lp-plan-name {
  font-size: 0.7rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.12em;
  color: var(--text-muted);
  margin-bottom: 0.75rem;
}
.lp-plan-price {
  font-family: var(--font-h2,'Plus Jakarta Sans'), sans-serif;
  font-size: 2.5rem; font-weight: 900;
  color: #fff; margin-bottom: 1.5rem;
  line-height: 1;
}
.lp-plan-price span { font-size: 0.9rem; font-weight: 500; color: var(--text-muted); margin-left: 0.1rem; }
.lp-plan-features {
  list-style: none;
  flex: 1;
  margin-bottom: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}
.lp-plan-features li {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.82rem;
  color: var(--text-muted);
}
.lp-plan-features li::before {
  content: '✓';
  color: var(--green);
  font-weight: 900;
  flex-shrink: 0;
  font-size: 0.75rem;
}
.lp-plan-miss { opacity: 0.45; text-decoration: line-through; }
.lp-plan-miss::before { content: '✕' !important; color: #EF4444 !important; }
@media (max-width: 1024px) { .lp-plans-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px)  { .lp-plans-grid { grid-template-columns: 1fr; } }

/* ════════════════════════════════════════════════════════════
   §10 — FAQ
════════════════════════════════════════════════════════════ */
.lp-faq { display: flex; flex-direction: column; gap: 0.75rem; }
.lp-faq-item {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: border-color var(--t);
}
.lp-faq-item[open] { border-color: var(--border-blue); }
.lp-faq-q {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.1rem 1.5rem;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 700;
  color: #fff;
  list-style: none;
  gap: 1rem;
  user-select: none;
}
.lp-faq-q::-webkit-details-marker { display: none; }
.lp-faq-q:hover { color: #BAE6FD; }
.lp-faq-arrow {
  font-size: 1.1rem;
  font-weight: 400;
  color: var(--blue);
  flex-shrink: 0;
  transition: transform var(--t);
}
.lp-faq-item[open] .lp-faq-arrow { transform: rotate(45deg); }
.lp-faq-a {
  padding: 0 1.5rem 1.25rem;
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.7;
}

/* ════════════════════════════════════════════════════════════
   §11 — CTA FINAL
════════════════════════════════════════════════════════════ */
.lp-cta {
  position: relative;
  padding: 8rem 0;
  text-align: center;
  overflow: hidden;
  background: linear-gradient(160deg, rgba(59,130,246,0.08) 0%, rgba(6,182,212,0.04) 50%, transparent 100%);
  border-top: 1px solid rgba(59,130,246,0.1);
}
.lp-cta-orb {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 600px; height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(59,130,246,0.12), transparent 60%);
  pointer-events: none;
}
.lp-cta-inner { display: flex; flex-direction: column; align-items: center; gap: 0; }
.lp-cta-title {
  font-family: var(--font-h1,'Plus Jakarta Sans'), sans-serif;
  font-size: clamp(2.2rem, 5vw, 4rem);
  font-weight: 900;
  color: #fff;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 1.25rem 0 1rem;
}
.lp-cta-sub {
  font-size: 1rem;
  color: var(--text-muted);
  max-width: 480px;
  margin-bottom: 2.5rem;
}
.lp-cta-actions { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }

/* ════════════════════════════════════════════════════════════
   FOOTER
════════════════════════════════════════════════════════════ */
.lp-footer {
  border-top: 1px solid var(--border);
  padding: 5rem 0 2.5rem;
  background: rgba(0,0,0,0.25);
}
.lp-footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3.5rem;
}
.lp-footer-logo {
  display: flex; align-items: center; gap: 0.55rem;
  font-weight: 900; font-size: 1rem; color: #fff;
  margin-bottom: 1rem;
}
.lp-footer-tagline { font-size: 0.82rem; color: var(--text-muted); line-height: 1.65; max-width: 260px; margin-bottom: 1.25rem; }
.lp-footer-social { display: flex; gap: 0.6rem; }
.lp-social-btn {
  width: 34px; height: 34px; border-radius: 9px;
  background: var(--bg-card); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 800; color: var(--text-muted);
  text-decoration: none; transition: all var(--t);
}
.lp-social-btn:hover { border-color: var(--border-blue); color: var(--blue); }
.lp-footer-col { display: flex; flex-direction: column; gap: 0.6rem; }
.lp-footer-col-title { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-muted); margin-bottom: 0.4rem; }
.lp-footer-link { font-size: 0.83rem; color: var(--text-muted); text-decoration: none; transition: color var(--t); }
.lp-footer-link:hover { color: var(--text); }
.lp-footer-bottom {
  padding-top: 2rem;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: var(--text-muted);
}
.lp-footer-made { opacity: 0.5; }
@media (max-width: 960px) { .lp-footer-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 600px) { .lp-footer-grid { grid-template-columns: 1fr; gap: 2rem; } .lp-footer-bottom { flex-direction: column; text-align: center; } }
</style>

