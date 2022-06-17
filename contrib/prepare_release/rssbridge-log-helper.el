;;; rssbridge-log-helper.el --- A helper for preparing RSS-Bridge releases  -*- lexical-binding:t; coding:utf-8 -*-

;;; Commentary:

;; Keyboard abbreviations used below:
;; C-x == Ctrl + x
;; M-x == Alt + x

;; How to use this helper?
;; 1. Run "git log --reverse 2021-04-25..master > tmp.md" (2021-04-25 is an example tag of a previous version)
;; 2. Copy the contents of template.md to the start of tmp.md
;; 3. Open Emacs. Type M-x load-file <ENTER>
;; 4. Enter in the path to rssbridge-log-helper.el then <ENTER>
;; 5. Type M-x find-file <ENTER>
;; 6. Enter the path to tmp.md then <ENTER>
;; 7. Type M-x rssbridge-log-transient-state <ENTER>
;; 8. You can now use the following shortcuts to organize the commits:
;;      x: Delete commit
;;      g: Copy as general change
;;      n: Copy as new bridge
;;      m: Copy as modified bridge
;;      r: Copy as removed bridge
;;      <any key>: Quit
;; 9. Once you are done with all the commits, type C-x then C-s
;; 10. Exit Emacs with C-x then C-c

;;; Code:

(defun rssbridge-log--get-commit-block ()
  "Select a commit block that begins before the cursor."
  (re-search-backward "^commit ") ;;  (move-beginning-of-line nil)
  (set-mark-command nil)
  (right-char)
  (re-search-forward "^commit ")
  (move-end-of-line 1))

(defun rssbridge-log--goto-first-commit ()
  "Find the first commit in the file."
  (goto-char (point-min))
  (re-search-forward "^commit "))

(defun rssbridge-log--remove-until-prev-commit-block ()
  "Remove from start of current line to previous commit block."
  (move-beginning-of-line nil)
  (set-mark-command nil)
  (re-search-backward "^commit ")
  (delete-region (region-beginning) (region-end)))

(defun rssbridge-log--remove-until-next-commit-block ()
  "Remove from start of current line to next commit block."
  (move-beginning-of-line nil)
  (set-mark-command nil)
  (re-search-forward "^commit ")
  (move-beginning-of-line nil)
  (delete-region (region-beginning) (region-end)))

(defun rssbridge-log--cut-paste (arg)
  "Copy current line to header that matches ARG."
  (kill-whole-line 0)
  (rssbridge-log--remove-until-next-commit-block)
  (goto-char (point-min))
  (re-search-forward arg)
  (move-end-of-line 1)
  (newline)
  (yank)
  (set-mark-command 1)
  (re-search-forward "^commit ")
  (recenter))

(defun rssbridge-log-remove ()
  "Remove the current commit block.

You can bind this function or use `rssbridge-log-transient-state'
to access the function."
  (interactive)
  (rssbridge-log--get-commit-block)
  (rssbridge-log--remove-until-prev-commit-block)
  (set-mark-command 1)
  (re-search-forward "^commit "))

(defun rssbridge-log-copy-as-new ()
  "Copy the current commit block as a new bridge.

You can bind this function or use `rssbridge-log-transient-state'
to access the function."
  (interactive)
  (rssbridge-log--get-commit-block)
  (re-search-backward "^.*\\[\\(.*\\)\\].*\\((.*)\\)" (region-beginning))
  (replace-match "* \\1 () \\2")
  (rssbridge-log--remove-until-prev-commit-block)
  (rssbridge-log--cut-paste "## New bridges"))

(defun rssbridge-log-copy-as-mod ()
  "Copy the current commit block as a modified bridge.

You can bind this function or use `rssbridge-log-transient-state'
to access the function."
  (interactive)
  (rssbridge-log--get-commit-block)
  (re-search-backward "^.*\\[\\(.*\\)\\]" (region-beginning))
  (replace-match "* \\1:")
  (rssbridge-log--remove-until-prev-commit-block)
  (rssbridge-log--cut-paste "## Modified bridges"))

(defun rssbridge-log-copy-as-gen ()
  "Copy the current commit block as a general change.

You can bind this function or use `rssbridge-log-transient-state'
to access the function."
  (interactive)
  (rssbridge-log--get-commit-block)
  (re-search-backward "^.*\\[\\(.*\\)\\]" (region-beginning))
  (replace-match "* \\1:")
  (rssbridge-log--remove-until-prev-commit-block)
  (rssbridge-log--cut-paste "## General changes"))

(defun rssbridge-log-copy-as-rem ()
  "Copy the current commit block as a removed bridge.

You can bind this function or use `rssbridge-log-transient-state'
to access the function."
  (interactive)
  (rssbridge-log--get-commit-block)
  (re-search-backward "^.*\\[\\(.*\\)\\]" (region-beginning))
  (replace-match "* \\1:")
  (rssbridge-log--remove-until-prev-commit-block)
  (rssbridge-log--cut-paste "## Removed bridges"))


(defun rssbridge-log-transient-state ()
  "Create a transient map for convienience.
x: Delete commit
g: Copy as general change
n: Copy as new bridge
m: Copy as modified bridge
r: Copy as removed bridge
<any key>: Quit"
  (interactive)
  (rssbridge-log--goto-first-commit)
  (set-transient-map
   (let ((map (make-sparse-keymap)))
     (define-key map "x" 'rssbridge-log-remove)
     (define-key map "g" 'rssbridge-log-copy-as-gen)
     (define-key map "n" 'rssbridge-log-copy-as-new)
     (define-key map "m" 'rssbridge-log-copy-as-mod)
     (define-key map "r" 'rssbridge-log-copy-as-rem)
     map)
   t))

(provide 'rssbridge-log-helper)
;;; rssbridge-log-helper.el ends here
