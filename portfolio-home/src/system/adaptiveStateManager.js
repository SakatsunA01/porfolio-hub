const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

export const resolveAdaptiveState = ({
  scrollDepth = 0,
  interactionCount = 0,
  hasActiveProject = false,
  isTransitioning = false,
  idleMs = 0,
  isProjectsView = false,
} = {}) => {
  const normalizedScroll = clamp(Number(scrollDepth), 0, 1)
  const normalizedInteractions = clamp(Number(interactionCount) / 6, 0, 1)
  const normalizedIdle = clamp(Number(idleMs) / 25000, 0, 1)

  let systemState = 'stable'
  let userMode = 'idle'

  if (isTransitioning || hasActiveProject) {
    systemState = 'syncing'
  } else if (normalizedScroll >= 0.42 || normalizedInteractions >= 0.6 || isProjectsView) {
    systemState = 'deep-exploration'
  }

  if (hasActiveProject || normalizedInteractions >= 0.5) {
    userMode = 'interacting'
  } else if (isProjectsView || normalizedScroll >= 0.18 || normalizedIdle < 0.45) {
    userMode = 'browsing'
  }

  if (normalizedIdle >= 0.7 && !hasActiveProject && !isTransitioning) {
    userMode = 'idle'
    if (systemState !== 'syncing') {
      systemState = 'stable'
    }
  }

  const accentStrength = systemState === 'syncing' ? 0.18 : systemState === 'deep-exploration' ? 0.12 : 0.08
  const blurBoost = systemState === 'syncing' ? 2.5 : systemState === 'deep-exploration' ? 1.2 : 0

  return {
    systemState,
    userMode,
    cssVars: {
      '--sys-accent': `rgba(16, 185, 129, ${accentStrength})`,
      '--sys-accent-strong': `rgba(16, 185, 129, ${accentStrength + 0.12})`,
      '--sys-surface-tint': systemState === 'syncing'
        ? 'rgba(236, 253, 245, 0.14)'
        : systemState === 'deep-exploration'
          ? 'rgba(248, 250, 252, 0.12)'
          : 'rgba(255, 255, 255, 0.08)',
      '--sys-panel-blur-boost': `${blurBoost}px`,
      '--sys-transition': '820ms',
    },
  }
}
