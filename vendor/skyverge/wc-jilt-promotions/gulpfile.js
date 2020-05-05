let gulp = require('gulp');
let wpPot = require('gulp-wp-pot');
let minify = require('gulp-minify');

gulp.task('compile', (done) => {

	// default compile tasks
	let tasks = ['makepot', 'minify'];

	gulp.parallel(tasks)(done)
});

gulp.task('makepot', function () {
	return gulp.src('src/**/*.php')
		.pipe(wpPot( {
			domain: 'sv-wc-jilt-promotions',
			package: 'SkyVerge Jilt Promotions'
		} ))
		.pipe(gulp.dest('src/i18n/languages/sv-wc-jilt-promotions.pot'));
});

gulp.task('minify', function() {
	return gulp.src('src/assets/js/**/*.js')
		.pipe(minify( {
			ext: {
				min: '.min.js'
			}
		} ))
		.pipe(gulp.dest('src/assets/js'))
});
