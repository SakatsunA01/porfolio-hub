const WEATHER_MODES = {
  clear: {
    backgroundTone: 'rgba(244, 251, 247, 0.18)',
    backgroundBrightness: 1.08,
    backgroundSaturation: 1.02,
    lightContrast: 1,
    blurIntensity: '10px',
    panelBlur: '24px',
    atmosphereOpacity: 0.72,
    cloudOpacity: 0.08,
    rainOpacity: 0,
    motionMultiplier: 1,
    environmentLabelKey: 'envBio',
  },
  cloudy: {
    backgroundTone: 'rgba(226, 232, 240, 0.22)',
    backgroundBrightness: 0.95,
    backgroundSaturation: 0.88,
    lightContrast: 0.96,
    blurIntensity: '14px',
    panelBlur: '28px',
    atmosphereOpacity: 0.86,
    cloudOpacity: 0.22,
    rainOpacity: 0.04,
    motionMultiplier: 0.78,
    environmentLabelKey: 'envCloudy',
  },
  rain: {
    backgroundTone: 'rgba(148, 163, 184, 0.2)',
    backgroundBrightness: 0.84,
    backgroundSaturation: 0.74,
    lightContrast: 0.93,
    blurIntensity: '18px',
    panelBlur: '32px',
    atmosphereOpacity: 0.94,
    cloudOpacity: 0.28,
    rainOpacity: 0.16,
    motionMultiplier: 0.62,
    environmentLabelKey: 'envRain',
  },
}

const RAIN_CODES = new Set([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99])
const CLOUDY_CODES = new Set([1, 2, 3, 45, 48])

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

export const resolveWeatherMode = (weatherCode) => {
  if (RAIN_CODES.has(weatherCode)) return 'rain'
  if (CLOUDY_CODES.has(weatherCode)) return 'cloudy'
  return 'clear'
}

export const resolveEnvironmentState = ({ weatherCode, humidity = 78, reducedMotion = false } = {}) => {
  const mode = resolveWeatherMode(Number(weatherCode))
  const profile = WEATHER_MODES[mode]
  const humidityFactor = clamp((Number(humidity) - 55) / 45, 0, 1)
  const motionMultiplier = reducedMotion ? 0 : profile.motionMultiplier
  const blurOffset = humidityFactor * 2

  return {
    mode,
    environmentLabelKey: profile.environmentLabelKey,
    cssVars: {
      '--env-tone': profile.backgroundTone,
      '--env-bg-brightness': `${profile.backgroundBrightness - humidityFactor * 0.03}`,
      '--env-bg-saturate': `${profile.backgroundSaturation - humidityFactor * 0.02}`,
      '--env-bg-contrast': `${profile.lightContrast}`,
      '--env-scene-blur': `blur(${Number.parseFloat(profile.blurIntensity) + blurOffset}px)`,
      '--env-panel-blur-px': `${Number.parseFloat(profile.panelBlur) + blurOffset}px`,
      '--env-panel-blur': `blur(${Number.parseFloat(profile.panelBlur) + blurOffset}px)`,
      '--env-atmosphere-opacity': `${profile.atmosphereOpacity}`,
      '--env-cloud-opacity': `${profile.cloudOpacity + humidityFactor * 0.08}`,
      '--env-rain-opacity': `${profile.rainOpacity}`,
      '--env-motion-multiplier': `${motionMultiplier}`,
      '--env-cloud-duration': `${Math.round(26000 / Math.max(profile.motionMultiplier, 0.2))}ms`,
      '--env-rain-duration': `${Math.round(1650 / Math.max(profile.motionMultiplier, 0.2))}ms`,
      '--env-cloud-play-state': reducedMotion ? 'paused' : 'running',
      '--env-rain-play-state': reducedMotion ? 'paused' : 'running',
      '--env-ui-transition': '900ms',
    },
  }
}
