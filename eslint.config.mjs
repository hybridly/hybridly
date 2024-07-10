import defineEslintConfig from '@innocenzi/eslint-config'

export default defineEslintConfig({
	// TODO: remove on eslint-config update
	markdown: true,
	rules: {
		'vue/multiline-html-element-content-newline': ['off'],
	},
})
